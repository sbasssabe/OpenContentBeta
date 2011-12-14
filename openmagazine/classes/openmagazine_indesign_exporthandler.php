<?php
/**
 * File containing the OpenMagazine_indesign_ExportHandler class.
 *
 * @version //autogen//
 * @copyright //autogen//
 * @license //autogen//
 * @author //autogen//
 */

class OpenMagazine_indesign_ExportHandler implements OpenMagazineExportHandlerInterface
{    
	static public function exportList( $result )
	{
        $ini = eZINI::instance();		
		$magazine = $result['container'];		

        if ( $magazine->attribute( 'class_identifier') == 'magazine_container' )
        {
            $pages = $magazine->children();
            self::loopChildren( $pages, $magazine, $result );
        }
        else
        {
            self::loopChildren( array( $magazine ), $magazine, $result );
        }

        
        return $result;
        
	}
    
    public static function loopChildren( $nodes, $parent, &$result )
    {
        
        foreach ( $nodes as $i => $node )
        {
            
            $order = intval( $node->attribute( 'priority' ) );
            if ( intval( $node->attribute( 'priority' ) ) == 0 )
                $order = $i;
            
            $url = 'openmagazine/export_idml/' . $node->attribute( 'node_id' ) . '/idml/';
            $nameFile = explode( '/', $node->attribute( 'url_alias' ) );
            $nameFile = substr( end( $nameFile ), 0, 20 ) . '-' . $node->attribute( 'node_id' );
            eZURI::transformURI( $url, false, 'full' );
            $result['data'][] = array(
                'url_file' 			=> $url,
                'last_modified' 	=> $node->object()->attribute( 'modified' ),
                'name'       		=> $node->attribute( 'name' ),
                'name_file'			=> $nameFile,
                'class_identifier'	=> $node->attribute( 'class_identifier' ),	
                'ext'			    => 'idml',
                'order'             => $order,
                'magazine_name'     => $parent->attribute( 'name' )
            );

            $url = 'openmagazine/export_idml/' . $node->attribute( 'node_id' ) . '/images/';
            eZURI::transformURI( $url, false, 'full' );
            
            $result['data'][] = array(
                'url_file' 			=> $url,
                'last_modified' 	=> $node->object()->attribute( 'modified' ),
                'name'       		=> $node->attribute( 'name' ),
                'name_file'			=> 'images_' . $nameFile,
                'class_identifier'	=> '(zip file)',
                'ext'			    => 'zip',
                'order'             => $order,
                'magazine_name'     => $parent->attribute( 'name' )
            );
            
            /*
            $exporter = new eZIdmlExporter( $node );;
            $exist = $exporter->haveImages(); 
                        
            if ( $exist )
            {
    
                $result['data'][] = array(
                    'url_file' 			=> $url,
                    'last_modified' 	=> $node->object()->attribute( 'modified' ),
                    'name'       		=> $node->attribute( 'name' ),
                    'name_file'			=> 'images_' . $nameFile,
                    'class_identifier'	=> '(zip file)',
                    'ext'			    => 'zip',
                    'order'             => $order,
                    'magazine_name'     => $parent->attribute( 'name' )
                );
            
            }
            */
        }
    }


}

?>
