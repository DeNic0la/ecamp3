<?php

namespace EcampWeb\Form;

use Zend\Form\Form;
use EcampCore\Validation\CampFieldset;

class CampUpdateForm extends BaseForm
{
    public function __construct($entityManager)
    {
        parent::__construct($entityManager);

        // add camp Fieldset
        $campFieldset = new CampFieldset($entityManager);
        $campFieldset->setUseAsBaseFieldset(true);
        $this->add($campFieldset);

        // � add CSRF and submit elements �
        $this->add(new \Zend\Form\Element\Csrf('security'));
        $this->add(array(
                'name' => 'send',
                'type'  => 'Submit',
                'attributes' => array(
                        'value' => 'Submit',
                ),
        ));

    }
}
