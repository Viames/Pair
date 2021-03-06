<?php

namespace Pair;

/**
 * Base abstract class to manage the HTML layout layer.
 */
abstract class View {

	/**
	 * Application object.
	 */
	protected $app;

	/**
	 * Path to the file, with trailing slash.
	 * @var string
	 */
	private $scriptPath = 'layouts/';

	/**
	 * Content variables for layout.
	 * @var array
	 */
	private $vars = array();

	/**
	 * Pagination variable.
	 * @var Pagination
	 */
	private $pagination;

	/**
	 * View name, without “View” suffix.
	 * @var string
	 */
	private $name;

	/**
	 * Layout file name, default as view name set by __construct().
	 * @var string
	 */
	protected $layout;

	/**
	 * Path to this module view with no trailing slash.
	 * @var string
	 */
	private $modulePath;

	/**
	 * Translator object.
	 * @var Translator
	 */
	protected $translator;

	/**
	 * Public URL for this module with no trailing slash.
	 * @var string
	 */
	private $moduleUrl;

	/**
	 * Model class object.
	 * @var mixed
	 */
	protected $model;

	/**
	 * Constructor.
	 */
	final public function __construct() {

		// singleton objects
		$this->app		= Application::getInstance();
		$router			= Router::getInstance();
		$this->translator = Translator::getInstance();

		// sets view name and default layout
		$class = get_called_class();
		$this->name = substr($class, 0, strpos($class, 'View'));
		$this->layout = strtolower(substr($class, strpos($class, 'View') + 4, 1)) . substr($class, strpos($class, 'View') + 5);

		// path to module folder
		$ref = new \ReflectionClass($this);
		$this->modulePath = dirname($ref->getFileName());

		// url to the module
		$this->moduleUrl = 'modules/' . strtolower($this->name); // BASE_HREF .

		// pagination
		$this->pagination			= new Pagination();
		$this->pagination->perPage	= Options::get('pagination_pages');
		$this->pagination->page		= $router->getPage();

		// includes and instance default model
		include_once ($this->modulePath .'/model.php');
		$modelName = $this->name . 'Model';
		$this->model = new $modelName();

		$this->model->pagination = $this->pagination;

		// sets the default menu item -- can be overwritten if needed
		$this->app->activeMenuItem = $router->module;

	}

	/**
	 * Formats page layout including variables and returns.
	 *
	 * @param	string	Layout file name without extension (.php).
	 *
	 * @return	string
	 */
	final public function display($name=NULL) {

		$this->render();

		// look for css files
		if (is_dir($this->modulePath . '/css')) {

			// get all folder files
			$files = Utilities::getDirectoryFilenames($this->modulePath . '/css');

			// load files as script and add timestamp to ignore browser caching
			foreach ($files as $file) {
				$fullPath = $this->moduleUrl . '/css/' . $file;
				$this->app->loadCss($fullPath . '?' . filemtime($fullPath));
			}

		}

		// look for javascript files
		if (is_dir($this->modulePath . '/js')) {

			// get all folder files
			$files = Utilities::getDirectoryFilenames($this->modulePath . '/js');

			// load files as script and add timestamp to ignore browser caching
			foreach ($files as $file) {
				$fullPath = $this->moduleUrl . '/js/' . $file;
				$this->app->loadScript($fullPath . '?' . filemtime($fullPath), TRUE);
			}

		}

		if (!$name) {
			$name = $this->layout;
		}

		$file = $this->modulePath .'/'. $this->scriptPath . $name .'.php';

		Logger::event('Applying ' . $this->layout . ' layout');

		// includes layout file
		try {

			if (file_exists($file)) {
				include $file;
			} else {
				throw new \Exception('Layout file ' . $file . ' was not found');
			}

		} catch (\Exception $e) {

			$this->app->enqueueError($e->getMessage());

		}

	}

	/**
	 * Adds a variable-item to the object array “vars”.
	 *
	 * @param	string	Variable-item name.
	 * @param	mixed	Variable-item value.
	 */
	public function assign($name, $val) {

		$this->vars[$name] = $val;

	}

	/**
	 * Restituisce, se esiste, la variabile assegnata al layout,
	 * altrimenti la proprietà del metodo, altrimenti NULL.
	 *
	 * @param	string	Nome della proprietà richiesta.
	 * @return	mixed
	 */
	public function __get($name) {

		if (array_key_exists($name, $this->vars)) {
			return $this->vars[$name];
		} else if (property_exists($this, $name)) {
			return $this->$name;
		} else {
			Logger::error('The ' . get_called_class() . '->' . $name. ' property doesn’t exist; Null will be returned');
			return NULL;
		}

	}

	/**
	 * Management of unknown view’s function.
	 *
	 * @param	string	$name
	 * @param	array	$arguments
	 */
	public function __call($name, $arguments) {

		$backtrace = debug_backtrace();
		Logger::error('Method '. get_called_class() . $backtrace[0]['type'] . $name .'(), which doesn’t exist, has been called by '. $backtrace[0]['file'] .' on line '. $backtrace[0]['line']);

	}

	final public function setState($name, $value) {

		$this->app->setState($name, $value);

	}

	/**
	 * Returns the requested session state variable.
	 *
	 * @param	integer	Variable’s name.
	 * @return	mixed
	 */
	final public function getState($name) {

		return $this->app->getState($name);

	}

	/**
	 * Appends a text message to queue.
	 *
	 * @param	string	Message’s text.
	 * @param	string	Optional title.
	 * @param	string	Message’s type (info, error).
	 */
	public function enqueueMessage($text, $title='', $type=NULL) {

		$this->app->enqueueMessage($text, $title, $type);

	}

	public function enqueueError($text, $title='') {

		$this->app->enqueueError($text, $title);

	}

	/**
	 * Adds an event to framework’s logger, storing its chrono time.
	 *
	 * @param	string	Event description.
	 * @param	string	Event type notice or error (default notice).
	 * @param	string	Optional additional text.
	 * @deprecated		Use static method Logger::event() instead.
	 */
	public function logEvent($description, $type='notice', $subtext=NULL) {

		Logger::event($description, $type, $subtext);

	}

	/**
	 * AddEvent’s proxy for warning event creations.
	 *
	 * @param	string	Event description.
	 * @deprecated		Use static method Logger::warning() instead.
	 */
	public function logWarning($description) {

		Logger::warning($description);

	}

	/**
	 * AddEvent’s proxy for error event creations.
	 *
	 * @param	string	Event description.
	 * @deprecated		Use static method Logger::error() instead.
	 */
	public function logError($description) {

		Logger::error($description);

	}

	/**
	 * Proxy function that returns a translated string.
	 *
	 * @param	string	The language key.
	 * @param	array	List of parameters to bind on string (optional).
	 */
	public function lang($key, $vars=NULL) {

		return Translator::do($key, $vars);

	}

	/**
	 * Proxy function that prints a translated string.
	 *
	 * @param	string	The language key.
	 * @param	array	List of parameters to bind on string (optional).
	 */
	public function _($key, $vars=NULL) {

		print Translator::do($key, $vars);

	}

	/**
	 * Computes data and assigns values to layout.
	 *
	 * @return	string
	 */
	abstract function render();

	/**
	 * Return the HTML code of pagination bar.
	 *
	 * @return string
	 */
	public function getPaginationBar() {

		if (is_null($this->pagination->count)) {
			Logger::error('The “count” parameter needed for pagination has not been set');
		}

		return $this->pagination->render();

	}

	/**
	 * Return an A-Z list with link for build an alpha filter.
	 *
	 * @param	string	Current selected list item, if any.
	 *
	 * @return	Generator
	 */
	public function getAlphaFilter($selected=NULL) {

		$router = Router::getInstance();

		foreach (range('A', 'Z') as $a) {

			$filter = new \stdClass();
			$filter->href	= $router->module . '/' . $router->action . '/' . strtolower($a) . '/page-1';
			$filter->text	= $a;
			$filter->active	= ($a == $selected);

			yield $filter;

		}

	}

	/**
	 * Returns the object of inherited class when called with id as first parameter.
	 *
	 * @param	string	Expected object class type.
	 * @return	ActiveRecord|NULL
	 */
	protected function getObjectRequestedById(string $class, ?int $pos=NULL): ?ActiveRecord {

		// reads from url requested item id
		$itemId = Router::get($pos ? abs($pos) : 0);

		if (!$itemId) {
			$this->enqueueError($this->lang('NO_ID_OF_ITEM_TO_EDIT', $class));
			return NULL;
		}

		$object = new $class($itemId);

		if (!$object->isLoaded()) {

			$this->enqueueError($this->lang('ID_OF_ITEM_TO_EDIT_IS_NOT_VALID', $class));
			Logger::error('Object ' . $class . ' id=' . $itemId . ' has not been loaded');
			return NULL;

		}

		return $object;

	}

	/**
	 * Get error list from an ActiveRecord object and show it to the user.
	 *
	 * @param	ActiveRecord	The inherited object.
	 */
	protected function raiseError(ActiveRecord $object) {

		// get error list from the ActiveRecord object
		$errors = $object->getErrors();

		// choose the error messages
		$message = $errors
			? implode(" \n", $errors)
			: $this->lang('ERROR_ON_LAST_REQUEST');

		// enqueue error message for UI
		$this->enqueueError($message);

		// after the message has been queued, store the error data
		ErrorLog::keepSnapshot('Failure in ' . \get_class($object) . ' class');

	}

}
