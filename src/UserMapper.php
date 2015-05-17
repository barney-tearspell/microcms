<?php

class UserMapper extends DB\Jig\Mapper {

	public function __construct()
	{
		parent::__construct(Base::instance()->get('DB'), 'users.json');
	}

}