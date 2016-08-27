<?php

namespace EdgarEz\ToolsBundle\Service;

use eZ\Publish\API\Repository\Exceptions\InvalidArgumentException;
use eZ\Publish\API\Repository\Exceptions\LimitationValidationException;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\Exceptions\UnauthorizedException;
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

        try {
            $roleStruct = $roleService->newRoleCreateStruct($roleName);
            $roleDraft = $roleService->createRole($roleStruct);
            $roleService->publishRoleDraft($roleDraft);
            return $roleService->loadRole($roleDraft->id);
        } catch (UnauthorizedException $e) {
            throw new \RuntimeException($e->getMessage());
        } catch (InvalidArgumentException $e) {
            throw new \RuntimeException($e->getMessage());
        } catch (LimitationValidationException $e) {
            throw new \RuntimeException($e->getMessage());
        }
    }

    public function addPolicy($roleID, $module, $function)
    {
        $this->repository->setCurrentUser($this->repository->getUserService()->loadUser($this->adminID));

        /** @var RoleService $roleService */
        $roleService = $this->repository->getRoleService();

        try {
            $policyStruct = $roleService->newPolicyCreateStruct($module, $function);
            $role = $roleService->loadRole($roleID);
            $roleDraft = $roleService->createRoleDraft($role);
            $roleDraft = $roleService->addPolicyByRoleDraft(
                $roleDraft,
                $policyStruct
            );

            $roleService->publishRoleDraft($roleDraft);
            return $roleDraft->id;
        } catch (UnauthorizedException $e) {
            throw new \RuntimeException($e->getMessage());
        } catch (NotFoundException $e) {
            throw new \RuntimeException($e->getMessage());
        } catch (InvalidArgumentException $e) {
            throw new \RuntimeException($e->getMessage());
        } catch (LimitationValidationException $e) {
            throw new \RuntimeException($e->getMessage());
        }
    }

    public function addSiteaccessLimitation(\eZ\Publish\API\Repository\Values\User\Role $role, array $siteaccess)
    {
        $this->repository->setCurrentUser($this->repository->getUserService()->loadUser($this->adminID));

        /** @var RoleService $roleService */
        $roleService = $this->repository->getRoleService();

        try {
            $roleDraft = $roleService->createRoleDraft($role);

            /** @var Policy[] $policies */
            $policies = $roleDraft->policies;
            foreach ($policies as $policy) {
                if ($policy->module == 'user' && $policy->function == 'login') {
                    $siteaccessLimitation = new Limitation\SiteAccessLimitation(
                        array(
                            'limitationValues' => $siteaccess
                        )
                    );

                    $policyUpdateStruct = new PolicyUpdateStruct();
                    $policyUpdateStruct->addLimitation($siteaccessLimitation);
                    $policyDraft = new PolicyDraft(['innerPolicy' => new Policy(['id' => $policy->id, 'module' => 'user', 'function' => 'login', 'roleId' => $roleDraft->id])]);

                    $roleService->updatePolicyByRoleDraft(
                        $roleDraft,
                        $policyDraft,
                        $policyUpdateStruct
                    );
                    $roleService->publishRoleDraft($roleDraft);
                    return;
                }
            }
        } catch (UnauthorizedException $e) {
            throw new \RuntimeException($e->getMessage());
        } catch (InvalidArgumentException $e) {
            throw new \RuntimeException($e->getMessage());
        } catch (LimitationValidationException $e) {
            throw new \RuntimeException($e->getMessage());
        }
    }
}
