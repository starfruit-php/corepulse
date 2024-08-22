<?php

namespace CorepulseBundle\Component\Field;

interface FieldInterface
{
    public function getName():string;

    public function getTitle():string;

    public function getValue();

    public function getDataSave();

    public function getFrontEndType():string;
}
