<?php
/**
 * File containing the OpenMagazine_xpress_ExportHandler class.
 *
 * @version //autogen//
 * @copyright //autogen//
 * @license //autogen//
 * @author //autogen//
 */

class OpenMagazine_xpress_ExportHandler implements OpenMagazineExportHandlerInterface
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
    
    /*
    static public function exportList( $result )
	{
        $ini = eZINI::instance();		
		$magazine = $result['container'];		
		
		
        if ( $magazine->attribute( 'class_identifier') == 'magazine_container' )
        {
            $pages = $magazine->children();
            foreach( $pages as $page )
            {
                $articles = $page->children();
                self::loopChildren( $articles, $page, $result );
            }
        }
        
        
        if ( $magazine ) 
		{
			$source_node = false;
            $dataMap = $magazine->dataMap();
            foreach( $dataMap as $attribute )
            {
                if ( $attribute->attribute( 'data_type_string' ) == 'ezidml' )
                {
                    $idmlPack = new eZIdmlPackageHandler( $attribute );
                    if ( $idmlPack->idml->hasAttribute( 'source_node_id' ) )
                    {
                        $source_node_id = $idmlPack->idml->attribute( 'source_node_id' );
                        $source_node = eZContentObjectTreeNode::fetch( $source_node_id );
                        if ( $source_node )
                        {
                            if ( !$source_node->childrenCount() )
                                $source_node = false;
                        }
                    }
                }
            }
            if ( $source_node )
            {
                self::loopChildren( $source_node->children(), $magazine, $result, false );
            }
            else
            {
                if ( $magazine->childrenCount() )
                {
                    $pages = $magazine->children();
                    
                    $page_is_magazine = true;
                    
                    foreach ( $pages as $page )
                    {
                        if ( $page->childrenCount() )
                        {
                            $page_is_magazine = false;
                            $articles = $page->children();
                            self::loopChildren( $articles, $page, $result );
                        }
                    }
                    
                    if ( $page_is_magazine )
                    {
                        self::loopChildren( $pages, $magazine, $result );
                    }
                    
                }
                else
                {
                    $result['errors'][] = 'Empty '. $magazine->attribute( 'name' );
                }
            }
		} 
		else 
		{
			$result['errors'][] = 'Not found';
		}
        
        
        return $result;
        
	}
	*/
    
    public static function loopChildren( $nodes, $parent, &$result )
    {
        $parentName = $parent ? $parent->attribute( 'name' ) : false;
        
        
        foreach ( $nodes as $i => $node )
        {
            if ( $node->attribute( 'class_identifier' ) == 'magazine_section' )
            {
                $order = intval( $node->attribute( 'priority' ) );
                if ( intval( $node->attribute( 'priority' ) ) == 0 )
                    $order = $i;
                
                $dataMap = $node->dataMap();
                foreach( $dataMap as $attribute )
                {
                    if ( $attribute->attribute( 'data_type_string' ) == 'ezidml' )
                    {
                        $idmlPack = new eZIdmlPackageHandler( $attribute );
                        $source_node_id = $idmlPack->idml->hasAttribute( 'source_node_id' ) ? $idmlPack->idml->attribute( 'source_node_id' ) : false;
                        if ( $source_node_id )
                        {
                            $sourceNode = eZContentObjectTreeNode::fetch( $source_node_id );
                            $children = $sourceNode->children();
                            foreach ( $children as $o => $child )
                            {
                                $subOrder = intval( $child->attribute( 'priority' ) );
                                if ( intval( $child->attribute( 'priority' ) ) == 0 )
                                    $subOrder = $o;
                                
                                $url = 'layout/set/xpress/content/view/xpress/' . $child->attribute( 'node_id' );
                                $nameFile = explode( '/', $child->attribute( 'url_alias' ) );
                                $nameFile = substr( end( $nameFile ), 0, 20 ) . '-' . $child->attribute( 'node_id' );
                                eZURI::transformURI( $url, false, 'full' );
                    
                                $result['data'][] = array(
                                    'url_file' 			=> $url,
                                    'last_modified' 	=> $child->object()->attribute( 'modified' ),
                                    'name'              => $child->attribute( 'name' ),	
                                    'name_file'			=> $nameFile,
                                    'class_identifier'	=> $child->attribute( 'class_identifier' ),								
                                    'ext' 			    => 'xtg',
                                    'order'             => 0 + ( $order . '.' . $subOrder ),
                                    'section_order'     => $order,
                                    'section_name'  	=> $node->attribute( 'name' ),
                                    'magazine_name'     => $parent->attribute( 'name' )
                                );
                                
                                $related_objects = $child->object()->relatedObjects( false, $child->ContentObjectID );
                                
                                foreach ( $related_objects as $related_object ) 
                                {
                                    if ( $related_object->attribute( 'class_identifier' ) == 'image' ) 
                                    {
                                        $data_map = $related_object->attribute( 'data_map' );									
                                        $image = eZImageFile::fetchForContentObjectAttribute( $data_map['image']->ID );
                                        $imageNameFile = explode( '/', $related_object->attribute('main_node')->attribute( 'url_alias' ) );
                                        $imageNameFile = substr( end( $imageNameFile ), 0, 20 ) . '-' . $related_object->attribute('main_node_id');
                                        #$result['data'][] = $data_map['image'] ;
                                        $url = $image[0];
                                        //eZURI::transformURI( $url, false, false );
                                        $sys = eZSys::instance();
                                        $result['data'][] = array(
                                            'url_file' 			=> $sys->serverURL() . '/' . $url,
                                            'last_modified' 	=> $related_object->attribute( 'modified' ),
                                            'name'              => $related_object->attribute( 'name' ),
                                            'name_file'			=> $imageNameFile,
                                            'class_identifier'	=> $related_object->attribute( 'class_identifier' ),
                                            'ext' 			    => eZFile::suffix( $url ),
                                            'order'             => 0 + ( $order . '.' . $subOrder ),
                                            'section_order'     => $order,
                                            'section_name'  	=> $node->attribute( 'name' ),
                                            'magazine_name'     => $parent->attribute( 'name' )
                    
                                        );
                                    }
                                }
                            }
                        }
                    }
                }
            }
                
        }
    }


}

?>
