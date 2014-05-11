<?php
namespace DBisso\Util\View;

/**
 * Template-agnostic view-model class
 */
abstract class View {
	/**
	 * The model this ViewModel represents
	 * @var stdClass
	 */
	protected $model;

	/**
	 * The name of the template
	 * @var string
	 */
	public $template_name;


	protected $template_prefix = 'templates';

	// /**
	//  * Where to find the templates
	//  * @var string
	//  */
	// $template_path = '';

	/**
	 * The template file name
	 * @var string
	 */
	// $template_file = '';


	// *
	//  * The method for rendering the template

	// $template_renderer;

	// /**
	//  * ?? The compiled template
	//  *
	//  * Perhaps this is just the result of load() in the simple case
	//  */
	// $template

	/**
	 * The data for the template
	 */
	protected $_data = array();

	public function __construct( $options ) {
		if ( ! empty( $options['model'] ) ) {
			$this->model = $options['model'];
		}

		if ( ! empty( $options['data'] ) ) {
			$this->custom_data = $options['data'];
		}

		if ( is_callable( array( $this, 'initialize' ) ) ) {
			$this->initialize();
		}
	}

	public function &__get( $name ) {
		if ( 'data' === $name ) {
			if ( is_null( $this->_data ) ) {
				$this->build_data();
			}

			return $this->_data;
		}
		return $this->$name;
	}

	public function template() {
		// First extract all model attributes
		if ( ! empty( $this->model ) ) {
			extract( (array) $this->model );
		}

		// Then any field data
		if ( is_array( $this->_data ) ) {
			extract( $this->_data );
		}

		// Finally any overrides or custom data specifed in the constructor
		if ( is_array( $this->custom_data ) ) {
			extract( $this->custom_data );
		}

		$template_path = $this->get_template_part( $this->get_template_name() );

		if ( file_exists( $template_path ) ) {
			include $template_path;
		} else {
			error_log(  sprintf( 'Template %s for view %s could not be found', $this->template_name, get_called_class() ) );
		}
	}

	private function get_template_name() {
		return $this->template_prefix . '/' . $this->template_name;
	}

	protected function get_template_part( $slug, $name = null ) {
		do_action( "get_template_part_{$slug}", $slug, $name );

		$templates = array();
		$name = (string) $name;
		if ( '' !== $name )
			$templates[] = "{$slug}-{$name}.php";

		$templates[] = "{$slug}.php";

		return locate_template( $templates, false, false );
	}

	private function build_data() {
		$reflection = new \ReflectionClass( $this );

		$field_methods = array_filter(
			$reflection->getMethods(), function( $method ) {
				return strpos( $method->name, 'field_' ) !== false;
			}
		);

		foreach ( $field_methods as $method ) {
			$field_name = str_replace( 'field_', '', $method->name );
			$this->_data[$field_name] = $method->invoke( $this );
		}
	}

	public function render() {
		$this->build_data();

		$content = '';

		ob_start();

		try {
			if ( is_callable( array( $this, 'template' ) ) ) {
				$this->template();
			}
			$content = ob_get_clean();
		} catch ( \Exception $e) {
			error_log( $e->getMessage() );
			ob_end_clean();
		}

		return $content;
	}

	public function __toString() {
		try {
			return $this->render();
		} catch ( \Exception $e ) {
			error_log( (string) $e );
			return '';
		}
	}


	public function display() {
		echo $this->__toString();
	}
}