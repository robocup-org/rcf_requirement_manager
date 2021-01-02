<?php
/**
 * @package  RCF Req Plugin
 */
namespace Inc;

use RCF_Module;

final class Init
{
	/**
	 * Store all the classes inside an array
	 * @return array Full list of classes
	 */
	public static function get_services() 
	{
		return [
			// RCF_Module::class,
			Pages\Admin::class,
			Base\AddUserRoles::class,
			Base\FilterAdminReq::class,
			Base\WorkFlow::class,
			Pages\Editorial_Comments::class,
			Base\Enqueue::class,
			Base\RequirementSave::class,
			Base\AddAcfFields::class,
			Base\AddCustomPost::class,
			Base\RevisionsMeta::class,
			Base\Activate::class,
		
		];
	}

	/**
	 * Loop through the classes, initialize them, 
	 * and call the register() method if it exists
	 * @return
	 */
	public static function register_services() 
	{
		foreach ( self::get_services() as $class ) {
			$service = self::instantiate( $class );
			if ( method_exists( $service, 'register' ) ) {
				$service->register();
			}
		}
	}

	/**
	 * Initialize the class
	 * @param  class $class    class from the services array
	 * @return class instance  new instance of the class
	 */
	private static function instantiate( $class )
	{
		$service = new $class();

		return $service;
	}
}
