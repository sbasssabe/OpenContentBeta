<?php
/**
 * File containing the ezidmlfunctionsjs class.
 *
 * @version //autogen//
 * @copyright //autogen//
 * @license //autogen//
 * @author //autogen//
 */

class ezidmlfunctionsjs extends ezjscServerFunctions
{
    
    static function fetchRepository()
    {
        $http = eZHTTPTool::instance();
        $tpl = eZTemplate::factory();

        $attributeID = $http->hasPostVariable( 'attributeID' ) ? $http->postVariable( 'attributeID' ) : false;
        $first = $http->hasPostVariable( 'first' ) ? $http->postVariable( 'first' ) : false;
        $last = $http->hasPostVariable( 'last' ) ? $http->postVariable( 'last' ) : false;
        $search = $http->hasPostVariable( 'search' ) ? $http->postVariable( 'search' ) : false;
        
        $limit = ( $last - $first ) > 0 ? ( $last - $first ) : 1;
        $offset = $first - 1;
        
        $tpl->setVariable( 'item', $first );
        $tpl->setVariable( 'limit', $limit );
        $tpl->setVariable( 'offset', $offset );
        $tpl->setVariable( 'search', $search );
        $tpl->setVariable( 'attribute_id', $attributeID );
        $template = $tpl->fetch( "design:openmagazine/svg-repository.tpl" );
        return $template;
        eZExecution::cleanExit();
    }
    
    static function fetchContent()
    {
        $http = eZHTTPTool::instance();
        $tpl = eZTemplate::factory();
        
        $nodeID = $http->hasPostVariable( 'nodeID' ) ? $http->postVariable( 'nodeID' ) : false;
        $contentID = $http->hasPostVariable( 'contentID' ) ? $http->postVariable( 'contentID' ) : false;

        if ( !$nodeID || !$contentID )
            return false;
        
        $node = eZContentObjectTreeNode::fetch( $nodeID );
        
        if ( !$node )
            return false;
        
        $dataMap = $node->dataMap();
        $ContentObjectAttribute = false;
        foreach( $dataMap as $attribute )
        {
            if ( $attribute->attribute( 'data_type_string' ) == 'ezidml' )
            {
                $ContentObjectAttribute = $attribute;
                break;
            }
                
        }
        if ( !$ContentObjectAttribute )
        {
            return false;
        }
        
        $idmlPackageHandler = new eZIdmlPackageHandler( $ContentObjectAttribute );
        $content = $idmlPackageHandler->idml->getContent( $contentID );
        
        if ( !$content )
        {
            return false;
        }
        
        if ( $content->hasAttribute( 'eZContentObjectTreeNodeID' ) )
        {
            $contentNode = eZContentObjectTreeNode::fetch( $content->attribute( 'eZContentObjectTreeNodeID' ) );
            if ( !$contentNode )
            {
                return false;
            }
            
            $currentVersion = $contentNode->object()->currentVersion();

            if ( $currentVersion && $content->hasAttribute( 'eZContentObjectAttributeID' ) )
            {
                $attribute = eZContentObjectAttribute::fetch( $content->attribute( 'eZContentObjectAttributeID' ), $currentVersion->attribute( 'version' ) );
                if ( !$attribute )
                {
                    return false;
                }
                
                $text = self::loadTpl( $content, $attribute );
           
                $text = strip_tags( $text );
                $checkText = str_replace( ' ', '', $text );
                if ( strlen( $checkText ) == 0 )
                    return false;
                
                $tpl->setVariable( 'idmlnode', $contentNode );
                $tpl->setVariable( 'idmlattribute', $attribute );
                $tpl->setVariable( 'text', $text );
                $tpl->setVariable( 'idmlcontent', $content );
                $template = $tpl->fetch( "design:openmagazine/svg-content-preview.tpl" );
                
                return $template;
            }
        }
        
    }

    private static function loadTpl( $idmlContent, $idmlContentObjectAttribute )
    {
        $tpl = eZTemplate::factory();
        if ( $idmlContent->hasAttribute( 'handler' ) )
        {
            $attributeHandler = $idmlContent->attribute( 'handler' );
            $tpl = call_user_func( array( $attributeHandler, 'tplSetVariable' ), $idmlContent, $idmlContentObjectAttribute, $tpl );
            $designPath = call_user_func( array( $attributeHandler, 'getDesignPath' ), $idmlContent, $idmlContentObjectAttribute, $tpl );
        }                
        else
        {
            if ( $idmlContent->hasAttribute( 'xmltag' ) )
            {
                $contentIni = eZINI::instance( 'content.ini' );
                if ( !in_array( $idmlContent->attribute( 'xmltag' ), $contentIni->variable( 'CustomTagSettings', 'AvailableCustomTags' ) ) )
                {
                    $tpl->setVariable( "classification", '' );
                    $tpl->setVariable( "content", '' );
                    $designPath = "design:content/datatype/view/ezxmltags/literal.tpl";
                }
                else
                {
                    $tmpDom = new DOMDocument;
                    $content = '';
        
                    if ( $tmpDom->loadXML( $idmlContentObjectAttribute->attribute( 'data_text' ) ) )
                    {
                        $eZTagName = 'custom[@name="' . $idmlContent->attribute( 'xmltag' ) . '"]';
                        $tmpXpath = new DOMXPath( $tmpDom );
                        $tmpNodeList = $tmpXpath->query( 'descendant::' . $eZTagName );
                        
                        $xmlTagPriority =  (int) $idmlContent->attribute( 'xmltag_priority' ) - 1;    
        
                        if ( !$tmpNodeList->length )
                        {
                            $content = '';
                        }
                        else
                        {
                            if ( $tmpNodeList->length >= $idmlContent->attribute( 'xmltag_priority' ) )
                            {
                                $content = $tmpNodeList->item( $xmlTagPriority )->nodeValue;
                            }
                        }
        
                    }
                    //set attributi
                    $tpl->setVariable( "content", $content );
                    $designPath = "design:content/datatype/view/ezxmltags/" . $idmlContent->attribute( 'xmltag' ) . ".tpl";
                }
            }
            else
            {
                $tpl->setVariable( "attribute", $idmlContentObjectAttribute );
                $designPath = "design:content/datatype/view/" . $idmlContentObjectAttribute->attribute( 'data_type_string' ) . ".tpl";
            }
        }
        return $tpl->fetch( $designPath );
    }

}

?>
