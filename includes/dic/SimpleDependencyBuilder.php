<?php

namespace SMW;

use InvalidArgumentException;
use OutOfBoundsException;

/**
 * Implements a basic DependencyBuilder
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * Basic implementation of the DependencyBuilder interface to enable access to
 * DependencyContainer objects and other invoked arguments
 *
 * @ingroup DependencyBuilder
 */
class SimpleDependencyBuilder implements DependencyBuilder {

	/** @var DependencyContainer */
	protected $dependencyContainer = null;

	/** @var integer */
	protected $objectScope = null;

	/**
	 * @note In case no DependencyContainer has been injected during construction
	 * an empty container is set as default to enable registration without the need
	 * to rely on constructor injection.
	 *
	 * @par Example:
	 * @code
	 *  $builder = new SimpleDependencyBuilder() or
	 *  $builder = new SimpleDependencyBuilder( new EmptyDependencyContainer() )
	 * @endcode
	 *
	 * @since  1.9
	 *
	 * @param DependencyContainer|null $dependencyContainer
	 */
	public function __construct( DependencyContainer $dependencyContainer = null ) {

		$this->dependencyContainer = $dependencyContainer;

		if ( $this->dependencyContainer === null ) {
			$this->dependencyContainer = new EmptyDependencyContainer();
		}

	}

	/**
	 * Registers a DependencyContainer
	 *
	 * @par Example:
	 * @code
	 *  $builder = new SimpleDependencyBuilder()
	 *
	 *  // Setter injection
	 *  $builder->registerContainer( new GenericDependencyContainer() )
	 *
	 *  // Register multiple container
	 *  $builder = new SimpleDependencyBuilder()
	 *  $builder->registerContainer( new GenericDependencyContainer() )
	 *  $builder->registerContainer( new AnotherDependencyContainer() )
	 * @endcode
	 *
	 * @since  1.9
	 *
	 * @param DependencyContainer $container
	 */
	public function registerContainer( DependencyContainer $container ) {
		$this->dependencyContainer->merge( $container->toArray() );
	}

	/**
	 * @see DependencyBuilder::getContainer
	 *
	 * @par Example:
	 * @code
	 *  $builder = new SimpleDependencyBuilder( new EmptyDependencyContainer() );
	 *  $builder->getContainer() returns EmptyDependencyContainer
	 *
	 *  // Register additional objects during runtime
	 *  $builder->getContainer()->registerObject( 'Title', new Title() ) or
	 *  $builder->getContainer()->registerObject( 'DIWikiPage', function ( DependencyBuilder $builder ) {
	 *  	return DIWikiPage::newFromTitle( $builder->getArgument( 'Title' ) );
	 *  } );
	 * @endcode
	 *
	 * @since  1.9
	 *
	 * @return DependencyContainer
	 */
	public function getContainer() {
		return $this->dependencyContainer;
	}

	/**
	 * Creates a new object
	 *
	 * @par Example:
	 * @code
	 *  $builder = new SimpleDependencyBuilder( ... )
	 *
	 *  $diWikiPage = $builder->newObject( 'DIWikiPage', array( $title ) ) or
	 *  $diWikiPage = $builder->addArgument( 'Title', $title )->newObject( 'DIWikiPage' );
	 * @endcode
	 *
	 * @since  1.9
	 *
	 * @param  string $objectName
	 * @param  array|null $objectArguments
	 *
	 * @return mixed
	 */
	public function newObject( $objectName, $objectArguments = null ) {
		return $this->setArguments( $objectArguments )->build( $objectName );
	}

	/**
	 * Build dynamic object entities via magic method __call
	 *
	 * @par Example:
	 * @code
	 *  $builder = new SimpleDependencyBuilder( ... )
	 *
	 *  // Register object
	 *  $builder->getContainer()->title = new Title()
	 *
	 *  // Retrieve object
	 *  $builder->title() returns Title object
	 * @endcode
	 *
	 * @param string $objectName
	 * @param array|null $objectArguments
	 *
	 * @return mixed
	 */
	public function __call( $objectName, $objectArguments = null ) {
		return $this->setArguments( $objectArguments )->build( $objectName );
	}

	/**
	 * @see DependencyBuilder::getArgument
	 *
	 * @note Arguments are being preceded by a "arg_" to distinguish those
	 * objects internally from registered DI objects. The handling is only
	 * relevant for those internal functions and hidden from the public
	 *
	 * @since 1.9
	 *
	 * @param string $key
	 *
	 * @return mixed
	 * @throws OutOfBoundsException
	 */
	public function getArgument( $key ) {

		if ( !( $this->hasArgument( $key ) ) ) {
			throw new OutOfBoundsException( "'{$key}' argument is invalid or unknown." );
		}

		return $this->dependencyContainer->get( 'arg_' . $key );
	}

	/**
	 * @see DependencyBuilder::hasArgument
	 *
	 * @since 1.9
	 *
	 * @param string $key
	 *
	 * @return mixed
	 * @throws InvalidArgumentException
	 */
	public function hasArgument( $key ) {

		if ( !is_string( $key ) ) {
			throw new InvalidArgumentException( "Argument is not a string" );
		}

		return $this->dependencyContainer->has( 'arg_' . $key );
	}

	/**
	 * @see DependencyBuilder::addArgument
	 *
	 * @since 1.9
	 *
	 * @param string $key
	 * @param mixed $value
	 *
	 * @return DependencyBuilder
	 * @throws InvalidArgumentException
	 */
	public function addArgument( $key, $value ) {

		if ( !is_string( $key ) ) {
			throw new InvalidArgumentException( "Argument is not a string" );
		}

		$this->dependencyContainer->set( 'arg_' . $key, $value );

		return $this;
	}

	/**
	 * @see DependencyBuilder::setScope
	 *
	 * @since  1.9
	 *
	 * @param $objectScope
	 *
	 * @return DependencyBuilder
	 */
	public function setScope( $objectScope ) {
		$this->objectScope = $objectScope;
		return $this;
	}

	/**
	 * Auto registration of arguments
	 *
	 * @since  1.9
	 *
	 * @param  array|null $objectArguments
	 *
	 * @return DependencyBuilder
	 */
	protected function setArguments( $objectArguments = null ) {

		if ( $objectArguments !== null && is_array( $objectArguments ) ) {
			foreach ( $objectArguments as $key => $value ) {
				$this->addArgument( get_class( $value ), $value );
			}
		}

		return $this;
	}

	/**
	 * Builds an object from a registered specification
	 *
	 * @since  1.9
	 *
	 * @param $object
	 *
	 * @return mixed
	 * @throws OutOfBoundsException
	 * @throws InvalidArgumentException
	 */
	protected function build( $objectName ) {

		if ( !is_string( $objectName ) ) {
			throw new InvalidArgumentException( 'Argument is not a string' );
		}

		if ( !$this->dependencyContainer->has( $objectName ) ) {
			throw new OutOfBoundsException( "{$objectName} is not registered" );
		}

		list( $objectSignature, $objectScope ) = $this->dependencyContainer->get( $objectName );

		return $this->load( $objectName, $objectSignature, $objectScope );
	}

	/**
	 * Resolves object definition and initializes an object in accordance with
	 * its scope
	 *
	 * @note Objects with a singleton scope are internally stored and preceded by
	 * a 'sing_' as object identifier
	 *
	 * @since  1.9
	 *
	 * @param string $objectName
	 * @param mixed $objectSignature
	 * @param integer $objectScope
	 *
	 * @return mixed
	 */
	private function load( $objectName, $objectSignature, $objectScope ) {

		// An object scope invoked during the build process has priority over
		// the original scope definition
		$objectScope = $this->objectScope !== null ? $this->objectScope : $objectScope;

		if ( $objectScope === DependencyObject::SCOPE_SINGLETON ) {

			$objectName = 'sing_' . $objectName;

			if ( !$this->dependencyContainer->has( $objectName ) ) {
				$this->dependencyContainer->set( $objectName, $this->singelton( $objectSignature ) );
			}

			$objectSignature = $this->dependencyContainer->get( $objectName );

		}

		// Reset internal scope variable
		$this->objectScope = null;

		return is_callable( $objectSignature ) ? $objectSignature( $this ) : $objectSignature;
	}

	/**
	 * Build singleton instance
	 *
	 * Keep the context within the closure static so any repeated call to
	 * this closure object will find the static context instead
	 *
	 * @param mixed $objectSignature
	 *
	 * @return Closure
	 */
	private function singelton( $objectSignature ) {

		// Resolve the object and use the result for static injection
		$object = is_callable( $objectSignature ) ? $objectSignature( $this ) : $objectSignature;

		return function() use ( $object ) {
			static $singleton;
			return $singleton = $singleton === null ? $object : $singleton;
		};

	}

}