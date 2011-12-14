<?php
/**
 * File containing the OpenMagazineExportHandler class.
 *
 * @version //autogen//
 * @copyright //autogen//
 * @license //autogen//
 * @author //autogen//
 */

class OpenMagazineExportHandler
{
    /**
     * Call preUpload or postUpload user definied functions.
     *
     * @static
     * @param string $method
     * @param array $result
     * @return bool
     */
    static function exec( $handler, $method, &$result )
    {
        $ini = eZINI::instance( 'openmagazine.ini' );
        $handlers = $ini->variable( 'ExportSettings', 'ExportHandlers' );
		$default_handler = $ini->variable( 'ExportSettings', 'DefaultHandler' );
		eZDebug::writeNotice( 'call handler '. $handler , __METHOD__ );

        if ( !$handlers )
		{
			eZDebug::writeWarning( 'OpenMagazine export handler configuration not found in openmagazine.ini', __METHOD__ );
            $handlers = array();
		}

        $done = false;
        
		if ( in_array( $handler, $handlers ) )
		{				
            if ( in_array( 'OpenMagazineExportHandlerInterface', class_implements( $handler ) ) )
			{
				if ( !method_exists( $handler, $method ) )
				{
					eZDebug::writeWarning( 'OpenMagazine export handler '. $handler . ' implementation: method not found', __METHOD__ );		
				}
                else
                {
                    $result = call_user_func( array( $handler, $method ), $result );
                    $done = true;
                }
			}			
		}
		
        if ( !$done )
		{
			if ( $handler != $default_handler )
                eZDebug::writeWarning( 'OpenMagazine export handler '. $handler . ' implementation not found, run default handler', __METHOD__ );		
			$result = call_user_func( array( $default_handler, $method ), $result );
		}				
		
        return true;
    }
}

?>
