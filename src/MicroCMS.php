<?php

class MicroCMS {

	const SCOPE_SEPARATOR = '.';

	protected $app, $debug;

	public function __construct()
	{
		Dotenv::load(__DIR__ . '/..');

		$this->app = Base::instance();

		$this->app->set('UI', './views/');
		$this->app->set('userLoggedIn', $this->app->exists('SESSION.user'));
		$this->app->mset($this->app->get('ENV'));

		$this->debug = $this->app->get('DEBUG');

		$this->setupErrorHandling();

		$this->setupAuthRoutes();

		$this->app->set('DB', new DB\Jig('data/'));

		//$user = new UserMapper();
		//$user->email = "admin@brain.com";
		//$user->password = md5("secret");
		//$user->save();
	}


	public function run()
	{
		$this->setupSynapseRoutes();
		$this->app->run();
	}


	public function route($pattern, $handler, $ttl = 0, $kbps = 0)
	{
		$this->app->route($pattern, $handler, $ttl, $kbps);
	}


	protected function setupAuthRoutes()
	{
		$cms = $this;

		$this->route('GET /login', function ($app) use ($cms)
		{
			$cms->getLogin();
		});
		$this->route('POST /login', function ($app) use ($cms)
		{
			$cms->postLogin();
		});
		$this->route('GET /logout', function ($app) use ($cms)
		{
			$cms->getLogout();
		});
	}

	protected function setupErrorHandling()
	{
		$this->app->set('ONERROR', function ($app)
		{
			$error = $app->get('ERROR');
			if ($error['code'] === 404 && ! $this->app->exists('userLoggedIn'))
			{
				$app->set('showLoginForm', true);
			}
			elseif($error['code'] === 404) 
			{
				$app->set('showPageForm', true);
			}

			echo Template::instance()->render('error.htm');
		});
	}

	protected function setupSynapseRoutes()
	{
		$cms = $this;

		$this->app->route('GET|POST|HEAD *', function ($app) use ($cms)
		{
			$cms->handleUrl();
		});
	}

	protected function handleUrl()
	{
		$uri = $this->app->get('URI');
		$uri = $uri === '/' ? $uri : substr($uri, 1);
		$uri = str_replace('/', '\/', $uri);

		$neuronMapper = new NeuronMapper();

		//$neuronMapper->synapse = 'about';
		//$neuronMapper->value = '<p>Copyright 2015</p>';
		//$neuronMapper->save();
		//$neuronMapper->reset();

		$neurons = $neuronMapper->find(array('preg_match(?, @_id)', '/^' . $uri . '/i'));
	
		if ($neurons)
		{
			$render = $this->renderNeuron($neurons[0]);
			if ($this->debug)
			{
				$style = '<style>span.synapse{background:red;color:white;}</style>';
				if (strpos($render, '</head>') !== FALSE)
				{
					$render = str_replace('</head>', $style . '</head>', $render);
				}
				else
				{
					$render = $style . $render;
				}
			}

			echo $render;
		}
		else
		{
			$this->app->error(404);
		}
	}

	protected function renderNeuron($neuron, $root = NULL)
	{
		$root = $root ?: $neuron;

		$synapses = array();
		$value = $neuron->value;
		preg_match_all('/\{\{(.+?)\}\}/i', $value, $synapses);
		if ( ! count($synapses[1]))
		{
			return $value;
		}

		foreach($synapses[1] as $synapse)
		{
			$render = $this->debug ? '<span class="synapse">' . $synapse . '</span>' : '';
			if (strpos($synapse, '$this' . self::SCOPE_SEPARATOR) === 0)
			{
				$render = $this->renderNeuronProperty($neuron, $this->getScopedSynapse($synapse)[1]) ?: $render;
			}
			elseif (strpos($synapse, '$page' . self::SCOPE_SEPARATOR) === 0)
			{
				$render = $this->renderNeuronProperty($root, $this->getScopedSynapse($synapse)[1]) ?: $render;
			}
			elseif (strpos($synapse, '$site' . self::SCOPE_SEPARATOR) === 0)
			{
				$render = $this->app->get(strtoupper($this->getScopedSynapse($synapse[1]))) ?: $render;
			}
			else
			{
				$neuronMapper = new NeuronMapper();
				$neurons = $neuronMapper->find(array('@_id = ?', $synapse));
				if ($neurons)
				{
					$render = $this->renderNeuron($neurons[0], $root);
				}
			}
			$value = str_replace('{{' . $synapse . '}}', $render, $value);
			$value = str_replace('data-source="' . $synapse . '"', '', $value);
		}

		return $value;
	}

	protected function getScopedSynapse($synapse)
	{
		return explode(self::SCOPE_SEPARATOR, $synapse);
	}

	public function renderNeuronProperty($neuron, $property)
	{
		return isset($neuron['data']) && isset($neuron['data'][$property]) ? $neuron['data'][$property] : '';
	}

	public function getLogin()
	{

	}


	public function postLogin()
	{
		if($this->app->exits('POST.email', $email) && $this->app->exits('POST.password', $password))
		{
			$userMapper = new UserMapper();
			$users = $userMapper->find(array('@email = ? && @password = ?', $email, md5($password)));
			if ($users) {
				$this->app->set('SESSION.user', $users[0]);
			}
		}
	}


	public function getLogout()
	{

	}

}