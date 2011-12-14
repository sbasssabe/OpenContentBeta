<?php
/**
 * File containing the eZIdmlImporter class.
 *
 * @version //autogen//
 * @copyright //autogen//
 * @license //autogen//
 * @author //autogen//
 */

class eZIdmlImporter
{
    public $idmlPackage; 
    public $idml;    
    public $defaultParams = array();
    public $params = array();
    
    public static function createOrUpdateEzContents( eZIdmlPackageHandler $idmlPackage, $parentNodeID, $params = array() )
    {
//eZDebug::writeError( 'Ci sto lavorando!', __METHOD__ );return false;
        return new eZIdmlImporter( $idmlPackage, $parentNodeID, $params );
    }
    
    function __construct( eZIdmlPackageHandler $idmlPackage, $parentNodeID, $params = array() )
    {
        $this->idmlPackage = $idmlPackage;
        $this->idml = $this->idmlPackage->idml;
        
        if ( !$this->idml->isValid() )
            return false;
        
        $children = array();
        
        $parentNode = eZContentObjectTreeNode::fetch( $parentNodeID );
        if ( $parentNode )
        {
            $classFilters = array();
            $classes = $this->idml->getContentClasses();
            if ( !empty( $classes ) )
            {
                $classFilters = array(
                    'ClassFilterType' => 'include',
                    'ClassFilterArray' => $classes
                );
            }  
            
            $childrenParams = array(
                'MainNodeOnly' => true,				
                'SortBy' => array( 'priority', true ),
                'AttributeFilter'  => array( array( 'priority', '>', 0 ) )
            );
            $childrenParams = array_merge( $childrenParams, $classFilters );
            $children = eZContentObjectTreeNode::subTreeByNodeID(  $childrenParams, $parentNode->attribute( 'node_id' ) );
        }
        else
        {
            eZDebug::writeError( 'Node not found for nodeID #' . $parentNodeID, __METHOD__ );
            return false;
        }        
        
        $user = eZUser::fetchByName( 'openmagazine' );
        if ( !$user )
        {
            $user = eZUser::currentUser();
        }
        
        $locale = eZLocale::instance();
        $languageCode = $locale->localeCode();

        $this->defaultParams = array(
            'remote_id'         => null,
            'section_id'        => 1,
            'creator_id'        => $user->id(),
            'class_identifier'  => null,
            'parent_node_id'    => $parentNodeID,
            'languageCode'      => $languageCode
        );  
        
        $this->setParams( $params );
        
        $contentObject = null;
        
        $contentTree = $this->idml->contentTree();
        
        foreach( $contentTree as $key => $contentArray )
        {
            $update = false;
            
            $classIdentifier = false;
        
            foreach( $contentArray as $idmlContent )
            {
                if ( !$classIdentifier )
                {
                    $classIdentifier = $idmlContent->attribute( 'class_identifier' );
                }
            }
            
            foreach ( $children as $node )
            {
                if ( $node->attribute( 'priority' ) == $key && $node->attribute( 'class_identifier' ) == $classIdentifier )
                {
                    $update = $node->object();
                }
            }
            
            $this->createOrUpdateObject( $contentArray, $key, $update );
        }
        
    }

    private function setParams( array $params )
    {        
        $this->params = array_merge( $this->defaultParams, $params );
    } 
    
    private function createOrUpdateObject( $contentArray, $priority, $update )
    {
        $classIdentifier = false;
        
        foreach( $contentArray as $idmlContent )
        {
            if ( !$classIdentifier )
            {
                $classIdentifier = $idmlContent->attribute( 'class_identifier' );
            }
            else
            {
                if ( $classIdentifier !== $idmlContent->attribute( 'class_identifier' ) )
                {
                    eZDebug::writeError( 'Error parsing idml content #' . $idmlContent->attribute( 'id' ), __METHOD__ );
                    return false;
                }
            }
        }    
        
        $parentNodeID = $this->params['parent_node_id'];
        
        if ( !$update )
        {            
            $creatorID = isset( $this->params['creator_id'] ) ? $this->params['creator_id'] : false;
    
            $contentObject = false;
    
            $parentNode = eZContentObjectTreeNode::fetch( $parentNodeID, false, false ); //as array
    
            if ( is_array( $parentNode ) )
            {
                $contentClass = eZContentClass::fetchByIdentifier( $classIdentifier );
                if ( is_object( $contentClass ) )
                {
                    $db = eZDB::instance();
                    $db->begin();
    
                    $contentObject = $contentClass->instantiate( $creatorID, 0, false, $this->params['languageCode'] );
    
                    if ( array_key_exists( 'remote_id', $this->params ) )
                        $contentObject->setAttribute( 'remote_id', $this->params['remote_id'] );
    
                    if ( array_key_exists( 'section_id', $this->params ) )
                        $contentObject->setAttribute( 'section_id', $this->params['section_id'] );
    
                    $contentObject->store();
    
                    $nodeAssignment = eZNodeAssignment::create( array( 'contentobject_id' => $contentObject->attribute( 'id' ),
                                                                       'contentobject_version' => $contentObject->attribute( 'current_version' ),
                                                                       'parent_node' => $parentNodeID,
                                                                       'is_main' => 1,
                                                                       'sort_field' => $contentClass->attribute( 'sort_field' ),
                                                                       'sort_order' => $contentClass->attribute( 'sort_order' ) ) );
                    $nodeAssignment->store();
    
                    $version = $contentObject->version( 1 );
                    $version->setAttribute( 'modified', eZDateTime::currentTimeStamp() );
                    $version->setAttribute( 'status', eZContentObjectVersion::STATUS_DRAFT );
                    $version->store();
    

                    $attributes = $contentObject->attribute( 'contentobject_attributes' );

                    foreach( $attributes as $attribute )
                    {
                        
                        $attributeIdentifier = $attribute->attribute( 'contentclass_attribute_identifier' );
                        $classAttribute = $contentClass->fetchAttributeByIdentifier( $attributeIdentifier );
                        $dataString = $this->getAttributeContent( $contentArray, $classAttribute );
                        if ( $dataString )
                        {
                            $attribute->fromString( $dataString );
                            $attribute->store();
                        }
                        
                    }
                    
                    $db->commit();
    
                    $operationResult = eZOperationHandler::execute( 'content', 'publish', array( 'object_id' => $contentObject->attribute( 'id' ),
                                                                                                 'version' => 1 ) );
                    $priorityArray = array( $priority );
                    $priorityIDArray = array( $contentObject->attribute( 'main_node_id' ) );
                    if ( eZOperationHandler::operationIsAvailable( 'content_updatepriority' ) )
                    {
                        $operationResult = eZOperationHandler::execute( 'content', 'updatepriority',
                                                                         array( 'node_id' => $parentNodeID,
                                                                                'priority' => $priorityArray,
                                                                                'priority_id' => $priorityIDArray ), null, true );
                    }
                    else
                    {
                        eZContentOperationCollection::updatePriority( $parentNodeID, $priorityArray, $priorityIDArray );
                    }                       
                    eZDebug::writeNotice( 'New content created from ' . $this->idml->attribute( 'idml_file' ) . ' at node #' . $contentObject->attribute( 'main_node_id' ), __METHOD__ );
                }
                else
                {
                    eZDebug::writeError( "Content class with identifier '$classIdentifier' doesn't exist.", __METHOD__ );
                }
            }
            else
            {
                eZDebug::writeError( "Node with id '$parentNodeID' doesn't exist.", __METHOD__ );
            }           
        }
        else
        {

            if ( array_key_exists( 'remote_id', $this->params ) )
            {
                $update->setAttribute( 'remote_id', $this->params['remote_id'] );
                $mustStore = true;
            }
    
            if ( array_key_exists( 'section_id', $this->params ) )
            {
                $update->setAttribute( 'section_id', $this->params['section_id'] );
                $mustStore = true;
            }
    
            if ( $mustStore )
                $update->store();
    
            if ( array_key_exists( 'languageCode', $this->params ) and $this->params['languageCode'] != false )
            {
                $languageCode = $this->params['languageCode'];
            }
            else
            {
                $initialLanguageID = $update->attribute( 'initial_language_id' );
                $language = eZContentLanguage::fetch( $initialLanguageID );
                $languageCode = $language->attribute( 'locale' );
            }
    
            $db = eZDB::instance();
            $db->begin();
    
            $newVersion = $update->createNewVersion( false, true, $languageCode );
    
            if ( !$newVersion instanceof eZContentObjectVersion )
            {
                eZDebug::writeError( 'Unable to create a new version for object ' . $object->attribute( 'id' ), __METHOD__ );
    
                $db->rollback();
    
                return false;
            }
    
            $newVersion->setAttribute( 'modified', time() );
            $newVersion->store();
    
            $attributeList = $newVersion->attribute( 'contentobject_attributes' );
            $contentClass = eZContentClass::fetchByIdentifier( $update->attribute( 'class_identifier' ) );
            foreach( $attributeList as $attribute )
            {
                $attributeIdentifier = $attribute->attribute( 'contentclass_attribute_identifier' );
                $classAttribute = $contentClass->fetchAttributeByIdentifier( $attributeIdentifier );
                $dataString = $this->getAttributeContent( $contentArray, $classAttribute );
                if ( $dataString )
                {
                    $attribute->fromString( $dataString );
                    $attribute->store();
                }
            }
    
            $db->commit();
    
            $operationResult = eZOperationHandler::execute( 'content', 'publish', array( 'object_id' => $newVersion->attribute( 'contentobject_id' ),
                                                                                         'version'   => $newVersion->attribute( 'version' ) ) );
            if( $operationResult['status'] == eZModuleOperationInfo::STATUS_CONTINUE )
            {
                $priorityArray = array( $priority );
                $priorityIDArray = array( $update->attribute( 'main_node_id' ) );
                if ( eZOperationHandler::operationIsAvailable( 'content_updatepriority' ) )
                {
                    $operationResult = eZOperationHandler::execute( 'content', 'updatepriority',
                                                                     array( 'node_id' => $parentNodeID,
                                                                            'priority' => $priorityArray,
                                                                            'priority_id' => $priorityIDArray ), null, true );
                }
                else
                {
                    eZContentOperationCollection::updatePriority( $parentNodeID, $priorityArray, $priorityIDArray );
                }
                eZDebug::writeNotice( 'Content at node #' . $update->attribute( 'main_node_id' ) . ' updated from ' . $this->idml->attribute( 'idml_file' ), __METHOD__ );
            }
            else
            {
                eZDebug::writeError( 'Error occurred updating node #' . $update->attribute( 'main_node_id' ) . ' from ' . $this->idml->attribute( 'idml_file' ), __METHOD__ );
            }            
        }
    }   
    
    private function getAttributeContent( array $idmlContentArray, eZContentClassAttribute $classAttribute )
    {
        
        foreach( $idmlContentArray as $idmlContent )
        {    
            if ( $idmlContent->attribute( 'attribute_identifier' ) == $classAttribute->attribute( 'identifier' ) )
            {
                eZDebug::writeNotice( $idmlContent->attribute( 'tag' ) . ' -> ' . $classAttribute->attribute( 'identifier') . ' (' . $classAttribute->attribute( 'data_type_string' ) . ')' , __METHOD__ );

                if ( $idmlContent->hasAttribute( 'xml_file' ) )
                {
                    if ( $idmlContent->hasAttribute( 'handler' ) )
                    {
                        $attributeHandler = $idmlContent->attribute( 'handler' );
                        $handlerReturn = call_user_func( array( $attributeHandler, 'getAttributeContent' ), $idmlContent, $classAttribute, $this->idmlPackage  );
                        if ( $handlerReturn )
                        {
                            return $handlerReturn;
                        }
                    }
                    
                    $xmlStory = simplexml_load_file( $this->idmlPackage->getXMLFile( $idmlContent->attribute( 'xml_file' ) ) );
                    
                    switch ( $classAttribute->attribute( 'data_type_string' ) )
                    {
                        
                        case 'ezxmltext':
                            
                            $xmlDeclarationStart = '<?xml version="1.0" encoding="utf-8"?><section xmlns:image="http://ez.no/namespaces/ezpublish3/image/" xmlns:xhtml="http://ez.no/namespaces/ezpublish3/xhtml/" xmlns:custom="http://ez.no/namespaces/ezpublish3/custom/">';
                            $xmlDeclarationEnd = '</section>';
                            
                            $xmlText = '';
                            
                            $xmlStoryParagraph = $xmlStory->xpath('//Story[@Self="' . $idmlContent->attribute( 'id' ) . '"]/descendant::XMLElement[@XMLContent="' . $idmlContent->attribute( 'id' ) . '"]/descendant::ParagraphStyleRange');

                            foreach ( $xmlStoryParagraph as $p )
                            {
                                $paragraphStyle = $p['AppliedParagraphStyle'];
                                
                                $isList = false;
                                
                                if ( isset( $p['BulletsAndNumberingListType'] ) )
                                {
                                    $isList = $p['BulletsAndNumberingListType'] == "BulletList" ? 'ul' : 'ol';
                                }    
                                
                                $character = $p->xpath( 'descendant::CharacterStyleRange' );
                                
                                if ( $character )
                                {
                                    $xmlText .= '<paragraph custom:class="' . self::cleanStyle( $paragraphStyle ) . '">';
                                    
                                    if ( $isList )
                                        $xmlText .= '<' . $isList . '><li>';

                                    $storyContent = array();
                                    foreach( $character as $ch )
                                    {
                                        $characterStyle = $ch['AppliedCharacterStyle'];
                                        $fontStyle = $ch['FontStyle'];
            
                                        $content = $ch->xpath( 'descendant::Content' );                                                                            
                                        
                                        foreach( $content as $c )
                                        {
                                            $content = preg_replace( '#\s+#', ' ', (string) $c );
                                            
                                            $italic = false;
                                            $bold = false;
                                            
                                            if ( strpos( strtolower( $fontStyle ), 'italic' ) !== false )
                                            {
                                                $italic = true;
                                            }
                                            
                                            if ( strpos( strtolower( $fontStyle ), 'bold' ) !== false )
                                            {
                                                $bold = true;
                                            }
                                            
                                            $br = $c->xpath( 'following-sibling::Br' );                                                                                    
                                            
                                            if ( $content !== '' )
                                            {
                                                $tpmContent = '';
                                                
                                                if ( $italic && $bold )
                                                {
                                                    $tpmContent = '<strong><emphasize>'. eZIdmlImporter::xmlentities( $content ) . '</emphasize></strong>';
                                                }
                                                elseif ( $italic )
                                                {
                                                    $tpmContent = '<emphasize>'. eZIdmlImporter::xmlentities( $content ) . '</emphasize>';
                                                }
                                                elseif ( $bold )
                                                {
                                                    $tpmContent = '<strong>'. eZIdmlImporter::xmlentities( $content ) . '</strong>';
                                                }
                                                else
                                                {
                                                    $tpmContent = eZIdmlImporter::xmlentities( $content );
                                                }
                                                
                                                if ( $isList )
                                                {
                                                    $storyContent[] = $tpmContent;
                                                }
                                                
                                                if ( $br )
                                                {
                                                    if ( $isList )
                                                    {
                                                        $storyContent[] = '</li><li>';
                                                    }
                                                    else
                                                    {                                        
                                                        $storyContent[] = '<line>'. $tpmContent . '</line>';
                                                        $storyContent[] = '</paragraph><paragraph custom:class="' . self::cleanStyle( $paragraphStyle ) . '">';
                                                    }
                                                }
                                                else
                                                {
                                                    if ( !$isList )
                                                    {
                                                        $storyContent[] = $tpmContent;
                                                    }
                                                }
                                                
                                                
                                            }
                                        }
                                    }                                    
                                    array_walk( $storyContent, 'trim' );                                   
                                    $xmlText .=  implode( " " , $storyContent );
                                    
                                    if ( $isList )
                                        $xmlText .= '</li></' . $isList . '>';
                                    
                                    $xmlText .= '</paragraph>';
                                }
                            
                                $xmlText = str_replace( '<paragraph custom:class="' . self::cleanStyle( $paragraphStyle ) . '"></paragraph>', '', $xmlText );

                            
                            }
                            
                            $xmlText = str_replace( '<li></li>', '', $xmlText );
                            
                            if ( $idmlContent->hasAttribute( 'have_child' ) )
                            {
                                $children = $idmlContent->getChildrenOrderedByXmltagPriority();
                                foreach( $children as $child )
                                {
                                    $handlerReturn = false;
                                    if ( $child->hasAttribute( 'handler' ) )
                                    {
                                        $attributeChildHandler = $child->attribute( 'handler' );
                                        $handlerReturn = call_user_func( array( $attributeChildHandler, 'getAttributeContent' ), $child, $classAttribute, $this->idmlPackage );
                                    }
                    
                                    if ( $handlerReturn )
                                    {
                                        $xmlText .= $handlerReturn;
                                    }
                                    else
                                    {
                                        if ( !$child->isImage() )
                                        {
                                            
                                            $contentIni = eZINI::instance( 'content.ini' );                                            
                                            if ( in_array( $child->attribute( 'xmltag' ), $contentIni->variable( 'CustomTagSettings', 'AvailableCustomTags' ) ) )
                                            {
                            
                                                if ( $child->hasAttribute( 'xml_file' ) )
                                                {
                                                    $xmlChildStory = simplexml_load_file( $this->idmlPackage->getXMLFile( $child->attribute( 'xml_file' ) ) );
                                                    $xmlChildStoryContent = $xmlChildStory->xpath('//Story[@Self="' . $child->attribute( 'id' ) . '"]/descendant::XMLElement[@XMLContent="' . $child->attribute( 'id' ) . '"]/descendant::Content');
            //$child->attribute( 'xmltag' )
                                                    $xmlText .= '<paragraph xmlns:tmp="http://ez.no/namespaces/ezpublish3/temporary/"><custom name="factbox" custom:title="factbox" custom:align="right"><paragraph xmlns:tmp="http://ez.no/namespaces/ezpublish3/temporary/">';
                                                    $xmlText .= eZIdmlImporter::xmlentities( eZIdmlImporter::getContentFromXML( $xmlChildStoryContent ) );
                                                    $xmlText .= '</paragraph></custom></paragraph>';                                                    
                                                }
                                                else
                                                {
                                                    $xmlChildStoryContent = $xmlStory->xpath('descendant::XMLElement[@MarkupTag="XMLTag/' . $child->attribute( 'tag' )  . '"]/descendant::Content');
                                                    
                                                    $xmlText .= '<paragraph xmlns:tmp="http://ez.no/namespaces/ezpublish3/temporary/"><custom name="factbox" custom:title="factbox" custom:align="right"><paragraph xmlns:tmp="http://ez.no/namespaces/ezpublish3/temporary/">';
                                                    $xmlText .= eZIdmlImporter::xmlentities( eZIdmlImporter::getContentFromXML( $xmlChildStoryContent ) );
                                                    $xmlText .= '</paragraph></custom></paragraph>';                                                    
                                                }
                                                
                                                eZDebug::writeNotice( $idmlContent->attribute( 'tag' ) . '/' . $child->attribute( 'tag' ) . ' -> ' . $classAttribute->attribute( 'identifier') . ' (' . $classAttribute->attribute( 'data_type_string' ) . ')' , __METHOD__ );
                                                
                                            }
                                        }
                                        else
                                        {
                                            $filePath = $child->getImageImportPath();
                                            $imageObjectID = false;
                                            if ( $filePath )
                                            {
                                                $imageObjectID = $this->importImage( $child );
                                                if ( $imageObjectID )
                                                {
                                                    $xmlText .= '<paragraph>';
                                                    $xmlText .= '<embed align="left" view="embed" size="small" object_id="' . $imageObjectID . '" />';
                                                    $xmlText .= '</paragraph>';
                                                    
                                                    eZDebug::writeNotice( $idmlContent->attribute( 'tag' ) . '/' . $child->attribute( 'tag' ) . ' -> ' . $classAttribute->attribute( 'identifier') . ' (' . $classAttribute->attribute( 'data_type_string' ) . ')' , __METHOD__ );
                                                    
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            eZDebug::writeNotice( $xmlDeclarationStart.$xmlText.$xmlDeclarationEnd , __METHOD__ );
                            return $xmlDeclarationStart.$xmlText.$xmlDeclarationEnd;
                                         
                            break;        
                        
                        default:
          
                            $xmlStory = simplexml_load_file( $this->idmlPackage->getXMLFile( $idmlContent->attribute( 'xml_file' ) ) );
                            $xmlStoryContent = $xmlStory->xpath('//Story[@Self="' . $idmlContent->attribute( 'id' ) . '"]/descendant::XMLElement[@XMLContent="' . $idmlContent->attribute( 'id' ) . '"]/descendant::Content');

                            eZDebug::writeNotice( eZIdmlImporter::getContentFromXML( $xmlStoryContent ) , __METHOD__ );
                            return eZIdmlImporter::getContentFromXML( $xmlStoryContent );
                        
                        break;
                    }
                    
                }
                else
                {
                    switch ( $classAttribute->attribute( 'data_type_string' ) )
                    {
                        
                        case 'ezimage':

                            if ( $idmlContent->getImageImportPath() )
                            {
                                $alternativeText = $this->getAlternativeTextFromIdmlImage( $idmlContent );

                                eZDebug::writeNotice( $idmlContent->getImageImportPath() . '|' . $alternativeText , __METHOD__ );
                                return $idmlContent->getImageImportPath() . '|' . $alternativeText;
                            }
                            else
                            {
                                return false;
                            }
                            
                            break;
                        
                        default:
          
                            $parentStory = $idmlPackage->idml->getContent( $idmlContent->attribute( 'parent_story_id' ) );
                            $xmlStory = simplexml_load_file( $idmlPackage->getXMLFile( $parentStory->attribute( 'xml_file' ) ) );  
                            $xmlChildStoryContent = $xmlStory->xpath('descendant::XMLElement[@MarkupTag="XMLTag/' . $idmlContent->attribute( 'tag' )  . '"]/descendant::Content');

                            eZDebug::writeNotice( eZIdmlImporter::getContentFromXML( $xmlChildStoryContent ) , __METHOD__ );                            
                            return eZIdmlImporter::getContentFromXML( $xmlChildStoryContent );
                        
                        break;                                                
                    }
                }
                
                break;
            }
        }
        
        return false;
    }
    
    private static function cleanStyle( $idmlStyle )
    {
        $style = 'default';
        
        $idmlStyle = str_replace( '/$ID/[No paragraph style]', '', $idmlStyle );
        $idmlStyle = str_replace( '/$ID/[No character style]', '', $idmlStyle );
        
        $styleArray = explode( '/', $idmlStyle );
        
        if ( isset( $styleArray[2] ) )
        {
            $style = str_replace( ' ', '-', $styleArray[2] );
        }
        elseif ( isset( $styleArray[1] ) )
        {
            if ( $styleArray[1] != '$ID' )
                $style = str_replace( ' ', '-', $styleArray[1] );
        }
            
            
        return $style;
    }
    
    private static function getContentFromXML( $xmlStoryContent )
    {
        $storyContent = array();
        foreach ( $xmlStoryContent as $c )
        {
            $content = preg_replace( '#\s+#',' ', (string) $c );
            if ( $content !== '' )
                $storyContent[] = $content;
        }
        array_walk( $storyContent, 'trim' );
        $storyContent = implode( ' ' , $storyContent );

        return $storyContent;
    }
    
    private function getAlternativeTextFromIdmlImage( $content )
    {
        $alternativeText = basename( $content->attribute( 'original_href' ) );
        
        if ( $content->hasAttribute( 'parent_story_id' ) )
        {
            $parentStory = $this->idmlPackage->idml->getContent( $content->attribute( 'parent_story_id' ) );             
            if ( $parentStory->haveChild() )
            {
                foreach ( $parentStory->attribute( 'children' ) as $child )
                {
                    if ( $child->attribute( 'xmltag' ) == 'alternative_text' && $child->attribute( 'xmltag_priority' ) == $content->attribute( 'xmltag_priority' ) )
                    {
                        if ( $child->hasAttribute( 'xml_file' ) )
                        {
                            $xmlChildStory = simplexml_load_file( $this->idmlPackage->getXMLFile( $child->attribute( 'xml_file' ) ) );
                            $xmlChildStoryContent = $xmlChildStory->xpath('//Story[@Self="' . $child->attribute( 'id' ) . '"]/descendant::XMLElement[@XMLContent="' . $child->attribute( 'id' ) . '"]/descendant::Content');
                            $alternativeText = eZIdmlImporter::getContentFromXML( $xmlChildStoryContent );
                        }
                        else
                        {
                            $xmlStory = simplexml_load_file( $this->idmlPackage->getXMLFile( $parentStory->attribute( 'xml_file' ) ) );   
                            $xmlChildStoryContent = $xmlStory->xpath('descendant::XMLElement[@MarkupTag="XMLTag/' . $child->attribute( 'tag' )  . '"]/descendant::Content');
                            $alternativeText = eZIdmlImporter::getContentFromXML( $xmlChildStoryContent );                        
                        }
                    }
                }
            }
        }
        
        return $alternativeText;
    }
    
    private function importImage( $content )
    {
        $filePath = $content->getImageImportPath();
        $alternativeText = $this->getAlternativeTextFromIdmlImage( $content );
        
        eZDebug::writeNotice( $content->attribute( 'tag' ) . ' -> ' . $filePath . '|' . $alternativeText , __METHOD__ );
        
        $result = array( 'errors' => array() );
        $upload = new eZContentUpload();
        $upload->handleLocalFile( $result, $filePath, 'auto', false );
        if ( isset( $result['contentobject'] ) )
        {
            $contentObject = $result['contentobject'];
            $dataMap = $contentObject->dataMap();
            foreach( $dataMap as $attribute )
            {
                if ( $attribute->attribute( 'data_type_string' ) == 'ezimage' )
                {
                    $content = $attribute->attribute( 'content' );
                    $content->setAttribute( 'alternative_text', $alternativeText );
                    $content->store( $attribute );
                }
            }
            return $contentObject->attribute('id');
        }
        return false;
    }
    
    private static function xmlentities($string) {
        return str_replace( array( '&', '"', "'", '<', '>' ), array( '&amp;' , '&quot;', '&apos;' , '&lt;' , '&gt;' ), $string );
    }

}
?>
