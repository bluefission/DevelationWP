<?php
namespace BlueFission\DevElation;

interface IDevObject
{
	public function field( $var, $value = null );
	public function clear();
}