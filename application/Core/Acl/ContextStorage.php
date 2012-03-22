<?php

namespace Core\Acl;


class ContextStorage
{
	
	
	/**
	 * @var Core\Repository\UserRepository
	 * @Inject Core\Repository\UserRepository 
	 */
	private $userRepo;
	
	/**
	 * @var Core\Repository\GroupRepository
	 * @Inject Core\Repository\GroupRepository
	 */
	private $groupRepo;
	
	/**
	 * @var Core\Repository\CampRepository
	 * @Inject Core\Repository\CampRepository
	 */
	private $campRepo;
	
	
	private $userId = null;
	private $groupId = null;
	private $campId = null;
	
	
	private $context = null;
	private $readonlyContext = null;
	
	
	
	public function set($userId, $groupId, $campId)
	{
		$this->context = null;
		$this->readonlyContext = null;
		
		$this->userId = $userId;
		$this->groupId = $groupId;
		$this->campId = $campId;
	}
	
	
	/**
	 * @return Core\Acl\Context
	 */
	public function getContext()
	{
		if(isset($this->context))
		{	return $this->context;	}
		
		
		$meId = \Zend_Auth::getInstance()->getIdentity();
		
		$userId =  $this->userId;
		$groupId = $this->groupId;
		$campId =  $this->campId;
		
		$me = 	 isset($meId) ? $this->userRepo->find(  $meId  ) : null;
		$user =  isset($userId)  ? $this->userRepo->find( $userId ) : null;
		$group = isset($groupId) ? $this->groupRepo->find($groupId) : null;
		$camp =  isset($campId)  ? $this->campRepo->find( $campId ) : null;
		
		$this->context = new Context($me, $user, $group, $camp);
		return $this->context;
	}
	
	
	/**
	 * @return CoreApi\Acl\Context
	 */
	public function getReadonlyContext()
	{
		if(isset($this->readonlyContext))
		{	return $this->readonlyContext;	}
		
		
		$meId = \Zend_Auth::getInstance()->getIdentity();
	
		$userId =  $this->userId;
		$groupId = $this->groupId;
		$campId =  $this->campId;
		
		$me = 	 isset($meId) 	 ? $this->userRepo->find(  $meId  ) : null;
		$user =  isset($userId)  ? $this->userRepo->find( $userId ) : null;
		$group = isset($groupId) ? $this->groupRepo->find($groupId) : null;
		$camp =  isset($campId)  ? $this->campRepo->find( $campId ) : null;
		
		$me = 	 isset($me) 	? $me->asReadonly() 	: null;
		$user =  isset($user) 	? $user->asReadonly() 	: null;
		$group = isset($group) 	? $group->asReadonly() 	: null;
		$camp =  isset($camp) 	? $camp->asReadonly() 	: null; 
		
		$this->readonlyContext = new \CoreApi\Acl\Context($me, $user, $group, $camp);
		return $this->readonlyContext;
	}
}