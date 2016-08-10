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

    public function add($roleName)
    {
        $this->repository->setCurrentUser($this->repository->getUserService()->loadUser($this->adminID));

        /** @var RoleService $roleService */
        $roleService = $this->repository->getRoleService();

        $roleStruct = $roleService->newRoleCreateStruct($roleName);
        $roleDraft = $roleService->createRole($roleStruct);
        $roleService->publishRoleDraft($roleDraft);
    }

    public function addPolicy($roleID, $module, $function)
    {
        $this->repository->setCurrentUser($this->repository->getUserService()->loadUser($this->adminID));

        /** @var RoleService $roleService */
        $roleService = $this->repository->getRoleService();

        $policyStruct = $roleService->newPolicyCreateStruct($module, $function);
        $roleDraft = $roleService->addPolicyByRoleDraft(
            $roleService->loadRoleDraftByRoleId($roleID),
            $policyStruct
        );

        $roleService->publishRoleDraft($roleDraft);
    }

    public function addLimitation($policyID, $roleID, Limitation $limitation)
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