<?php

class MicroCMS {

	const SCOPE_SEPARATOR = '.';

	protected $app;

	public function __construct(Base $app)
	{
		Dotenv::load(__DIR__ . '/..');

		$this->app = $app;

		$this->app->set('UI', './views/');

		$this->setupErrorHandling();

		$this->setupAuthRoutes();

		$this->app->set('DB', new DB\Jig('data/'));
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
			// todo check if user loged in
			if ($error['code'] === 404)
			{
				$app->set('showLoginForm', true);
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
		$neurons = $neuronMapper->find(array('preg_match(?, @_id)', '/^' . $uri . '/i'));
		if ($neurons)
		{
			echo $this->renderNeuron($neurons[0]);
		}
		else
		{
			$this->app->error(404);
		}
	}

	protected function renderNeuron($neuron)
	{
		$synapses = array();
		$value = $neuron->value;
		preg_match_all('/\{\{(.+)\}\}/i', $value, $synapses);
		if ( ! count($synapses[1]))
		{
			return $value;
		}

		foreach($synapses[1] as $synapse)
		{
			if (strpos($synapse, '$this' . self::SCOPE_SEPARATOR) === 0)
			{
				$render = isset($neuron['data']) && isset($neuron['data'][str_replace('$this' . self::SCOPE_SEPARATOR,
							'', $synapse)])
				$neuron['data'][str_replace('$this' . self::SCOPE_SEPARATOR, '', $synapse)];
			}
			elseif (strpos($synapse, '$page' . self::SCOPE_SEPARATOR) === 0)
			{

			}
			elseif (strpos($synapse, '$page' . self::SCOPE_SEPARATOR) === 0)
			{

			}
			$neuronMapper = new NeuronMapper();
			$neurons = $neuronMapper->find(array('@_id = ?', $synapse));
			if ( ! $neurons)
			{
				throw new BrainFart('Synapse found leading to a non-existing neuron in neuron "' . $neuron->id . '"');
			}
			$render = $this->renderNeuron($neurons[0]);
		}

		$value = str_replace('{{' . $synapse . '}}', $render, $value);
		$value = str_replace('data-source="' . $synapse . '"', '', $value);

		return $value;
	}

	public function getLogin()
	{

	}


	public function postLogin()
	{

	}


	public function getLogout()
	{

	}

}