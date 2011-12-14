<?php
/**
 * File containing the OpenMagazine_default_ExportHandler class.
 *
 * @version //autogen//
 * @copyright //autogen//
 * @license //autogen//
 * @author //autogen//
 */

class OpenMagazine_default_ExportHandler implements OpenMagazineExportHandlerInterface
{
    static public function exportList( $result )
	{
		$magazines = $result['container'];
		$result['errors'][] = 'Default Handler: no data';
        $type = $result['export_view'];
        
        $ini = eZINI::instance( 'openmagazine.ini' );
        $status = ( $ini->hasVariable( 'ExportSettings', 'MagazineCompletedAttributeIdentifier' ) ) ? $ini->variable( 'ExportSettings', 'MagazineCompletedAttributeIdentifier' ) : false;
        $handlers = $ini->variable( 'ExportSettings', 'ExportHandlers' );
        $handler = 'OpenMagazine_' . $type . '_ExportHandler';
        if ( !in_array( $handler, $handlers ) && $type !== 'default')
        {
            $result['errors'][] = 'OpenMagazine export handler configuration not found in openmagazine.ini';
            return $result;
        }
        
        $result['errors'] = array();
        
        if ( !is_array( $magazines ) )
        {
            $parent = $magazines;
            $magazines = $magazines->children();
            $loopChildren = true;
        }

        foreach( $magazines as $i => $node )
        {
            $continue = true;
            if ( !$loopChildren && $status )
            {
                $dataMap = $node->dataMap();
                foreach( $dataMap as $attribute )
                {
                    if ( $attribute->attribute( 'data_type_string' ) == 'ezboolean' &&  $attribute->attribute( 'contentclass_attribute_identifier' ) == $status )
                    {
                        if ( $attribute->toString() == '0' )
                            $continue = false;
                    }
                }
            }
            if ( $continue )
            {
                $order = intval( $node->attribute( 'priority' ) );
                if ( intval( $node->attribute( 'priority' ) ) == 0 )
                    $order = $i;
                
                $url = 'openmagazine/list/' . $node->attribute( 'node_id' ) . '/' . $type . '/';
                eZURI::transformURI( $url, false, 'full' );
                $data = array(
                    'url_file' 			=> $url,
                    'last_modified' 	=> $node->attribute( 'modified_subnode' ),
                    'name'              => $node->attribute( 'name' ),
                    'class_identifier'  => $node->attribute( 'class_identifier' ),
                    'order'    => $order,
                );
                
                $childrenData = array();
                 
                if ( $loopChildren )
                {
                    $childrenData = array(
                        'order'    => $order,
                        'magazine_name'    => $parent->attribute( 'name' )
                    );
                }
                
                $result['data'][] = array_merge( $data, $childrenData );
            }
        }
        
        return $result;
    }
}

?>
