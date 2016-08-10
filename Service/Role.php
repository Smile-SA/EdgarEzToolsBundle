<?php

namespace EdgarEz\ToolsBundle\Service;

use eZ\Publish\API\Repository\Repository;
use eZ\Publish\API\Repository\RoleService;
use eZ\Publish\API\Repository\Values\User\Limitation;
use eZ\Publish\Core\Repository\Values\User\Policy;
use eZ\Publish\Core\Repository\Values\User\PolicyDraft;
use eZ\Publish\Core\REST\Client\Values\User\PolicyUpdateStruct;

class Role
{
    private $repository;

    /**
     * @var int admin user id
     */
    private $adminID;

    /**
     * adminID setter
     *
     * @param int $adminID
     */
    public function setAdminID($adminID)
    {
        $this->adminID = $adminID;
    }

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param $roleName
     * @return \eZ\Publish\API\Repository\Values\User\Role
     */
    public function add($roleName)
    {
        $this->repository->setCurrentUser($this->repository->getUserService()->loadUser($this->adminID));

        /** @var RoleService $roleService */
        $roleService = $this->repository->getRoleService();

        $roleStruct = $roleService->newRoleCreateStruct($roleName);
        $roleDraft = $roleService->createRole($roleStruct);
        $roleService->publishRoleDraft($roleDraft);
        return $roleService->loadRole($roleDraft->id);
    }

    public function addPolicy($roleID, $module, $function)
    {
        $this->repository->setCurrentUser($this->repository->getUserService()->loadUser($this->adminID));

        /** @var RoleService $roleService */
        $roleService = $this->repository->getRoleService();

        $policyStruct = $roleService->newPolicyCreateStruct($module, $function);
        $role = $roleService->loadRole($roleID);
        $roleDraft = $roleService->createRoleDraft($role);
        $roleDraft = $roleService->addPolicyByRoleDraft(
            $roleDraft,
            $policyStruct
        );

        $roleService->publishRoleDraft($roleDraft);
        return $roleDraft->id;
    }

    public function addLimitation($roleID, Limitation $limitation)
    {
        $this->repository->setCurrentUser($this->repository->getUserService()->loadUser($this->adminID));

        /** @var RoleService $roleService */
        $roleService = $this->repository->getRoleService();
    }

    public function addPolicyLimitation($policyID, $roleID, Limitation $limitation)
    {
        $this->repository->setCurrentUser($this->repository->getUserService()->loadUser($this->adminID));

        /** @var RoleService $roleService */
        $roleService = $this->repository->getRoleService();

        $policy = new Policy(
            array(
                'id' => $policyID,
                'roleId' => $roleID,
            )
        );

        $policyUpdateStruct = new PolicyUpdateStruct();
        $policyStruct = $policyUpdateStruct->addLimitation($limitation);
        $policyDraft = new PolicyDraft(['innerPolicy' => $policy]);
        $roleDraft = $roleService->loadRoleDraftByRoleId($roleID);

        $roleService->updatePolicyByRoleDraft(
            $roleDraft,
            $policyDraft,
            $policyStruct
        );
        $roleService->publishRoleDraft($roleDraft);
    }
}
