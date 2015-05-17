<?php

class NeuronMapper extends \DB\Jig\Mapper {

	public function __construct()
	{
		parent::__construct(Base::instance()->get('DB'), 'neurons.json');
	}

	function insert()
	{
		if ($this->id && $this->find(array('@_id = ?', $this->id)))
		{
			return $this->update();
		}
		elseif ( ! $this->id) {
			throw new BrainFart('You must declare a synapse for a neuron! [Missing ID]');
		}
		$db  = $this->db;
		$now = microtime(true);
		$pkey     = array('_id' => $this->id);
		if (isset($this->trigger['beforeinsert']))
		{
			\Base::instance()->call($this->trigger['beforeinsert'], array($this, $pkey));
		}
		$data =& $db->read($this->file);
		$data[$this->id] = $this->document;
		$db->write($this->file, $data);
		$db->jot('(' . sprintf('%.1f',
				1e3 * (microtime(true) - $now)) . 'ms) ' . $this->file . ' [insert] ' . json_encode($this->document));
		if (isset($this->trigger['afterinsert']))
		{
			\Base::instance()->call($this->trigger['afterinsert'], array($this, $pkey));
		}
		$this->load(array('@_id=?', $this->id));

		return $this->document;
	}

	public function &get($key)
	{
		if($key === 'synapse')
		{
			$key = '_id';
		}
		return parent::get($key);
	}

	public function set($key, $val)
	{
		if ($key === 'synapse')
		{
			$this->id = $val;
		}
		else
		{
			return parent::set($key, $val);
		}
	}

}