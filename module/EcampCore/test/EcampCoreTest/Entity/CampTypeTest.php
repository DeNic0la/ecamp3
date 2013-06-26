<?php

namespace EcampCoreTest\Entity;

use EcampCore\Entity\CampType;

class CampTypeTest extends \PHPUnit_Framework_TestCase
{

	public function testCampType()
	{
		$campType = new CampType();
		$campType->setName('any camp type');
		$campType->setType('any type');
		
		$this->assertEquals('any camp type', $campType->getName());
		$this->assertEquals('any type', $campType->getType());
		$this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $campType->getEventTypes());
	}
    
}
