<?php
/**
 * File containing the OpenMagazineContentTypeHandler class.
 *
 * @version //autogen//
 * @copyright //autogen//
 * @license //autogen//
 * @author //autogen//
 */

class OpenMagazineContentTypeHandler
{
    static public function json( $result )
    {
		header('Content-type: application/json');		
		echo json_encode( array (
			'result' => $result
		) );
    }
	
	# @TODO
	static public function xml( $result )
    {
		header('Content-type: text/xml');
		echo "<root><error>ContentType not yet implemented</error></root>";
    }
	
	static public function debug( $result )
    {
		header('Content-type: text/html');
		echo '<pre>';
        print_r( $result );
        echo '</pre>';
        eZDisplayDebug();
    }	
}

?>
