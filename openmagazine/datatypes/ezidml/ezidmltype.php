<?php
/**
 * File containing the eZIdmlType class.
 *
 * @version //autogen//
 * @copyright //autogen//
 * @license //autogen//
 * @author //autogen//
 */

class eZIdmlType extends eZDataType
{
    const DATA_TYPE_STRING = 'ezidml';

    static $create_or_update_ez_contents = false;

    function __construct()
    {
        parent::__construct( self::DATA_TYPE_STRING, "Idml Layout",
                            array( 'serialize_supported' => true ) );
    }

    function initializeObjectAttribute( $contentObjectAttribute, $currentVersion, $originalContentObjectAttribute )
    {        
        if ( $currentVersion != false )
        {
            $idml = $originalContentObjectAttribute->content();
            $clonedIdml = clone $idml;
            $contentObjectAttribute->setContent( $clonedIdml );
            $idmlPackage = new eZIdmlPackageHandler( $contentObjectAttribute );
            $idmlPackage->initializeFromExistingObject();
            $contentObjectAttribute->setContent( $idmlPackage->idml );
            $contentObjectAttribute->store();
        }
        else
        {
            $idml = new eZIdmlDoc();
            $contentObjectAttribute->setContent( $idml );
        }
    }

    function hasObjectAttributeContent( $contentObjectAttribute )
    {
        $idml = $contentObjectAttribute->content();
        if ( $idml->hasAttribute( 'idml_file' ) )
            return true;
        return false;
    }

    function validateClassAttributeHTTPInput( $http, $base, $classAttribute )
    {
        return eZInputValidator::STATE_ACCEPTED;
    }

    function fetchClassAttributeHTTPInput( $http, $base, $classAttribute )
    {
        return true;
    }

    function validateObjectAttributeHTTPInput( $http, $base, $contentObjectAttribute )
    {
        return eZInputValidator::STATE_ACCEPTED;
    }

    function fetchObjectAttributeHTTPInput( $http, $base, $contentObjectAttribute )
    {
        $idmlPackage = new eZIdmlPackageHandler( $contentObjectAttribute );
        
        eZIdmlType::checkFileUploads();
        $httpFileName = $base . "_data_idmlname_" . $contentObjectAttribute->attribute( "id" );
        
        if ( eZHTTPFile::canFetch( $httpFileName ) )
        {
            $httpFile = eZHTTPFile::fetch( $httpFileName );
            if ( $httpFile )
            {
                $idmlPackage->reset( true );
                $idmlPackage->initializeFromHTTPFile( $httpFile );
            }
        }
        
        if ( $http->hasPostVariable( $base . "_create_or_update_ez_contents_" . $contentObjectAttribute->attribute( "id" ) ) )
        {
            eZIdmlType::$create_or_update_ez_contents = true;
        }
        
        if ( $http->hasPostVariable( $base . "_import_ez_contents_" . $contentObjectAttribute->attribute( "id" ) ) )
        {
            $idmlPackage->setImportEzContents();
        }
        else
        {
            $idmlPackage->unsetImportEzContents();
        }
        eZIdmlPackageHandler::importEzContents( $idmlPackage->idml );
        $contentObjectAttribute->setContent(  $idmlPackage->idml );
        $contentObjectAttribute->store();
        
        return true;
    }

    function storeObjectAttribute( $contentObjectAttribute )
    {
        $idml = $contentObjectAttribute->content();
        $contentObjectAttribute->setAttribute( 'data_text', $idml->toXML() );
        return true;
    }

    function objectAttributeContent( $contentObjectAttribute )
    {
        $source = $contentObjectAttribute->attribute( 'data_text' );
        $idml = eZIdmlDoc::createFromXML( $source );

        if ( eZIdmlPackageHandler::importEzContents( $idml ) )
        {
            $contentObjectAttribute->setAttribute( 'data_text', $idml->toXML() );
            $contentObjectAttribute->store();
            $source = $contentObjectAttribute->attribute( 'data_text' );
            $idml = eZIdmlDoc::createFromXML( $source );
        }
        return $idml;
    }

    function metaData( $contentObjectAttribute )
    {
        return false;
    }

    function title( $contentObjectAttribute, $name = null  )
    {
        return '';
    }

    function checkFileUploads()
    {
        $isFileUploadsEnabled = ini_get( 'file_uploads' ) != 0;
        if ( !$isFileUploadsEnabled )
        {
            $isFileWarningAdded = $GLOBALS['eZIdmlTypeWarningAdded'];
            if ( !isset( $isFileWarningAdded ) or
                 !$isFileWarningAdded )
            {
                eZAppendWarningItem( array( 'error' => array( 'type' => 'kernel',
                                                              'number' => eZError::KERNEL_NOT_AVAILABLE ),
                                            'text' => ezpI18n::tr( 'kernel/classes/datatypes',
                                                              'File uploading is not enabled. Please contact the site administrator to enable it.' ) ) );
                $GLOBALS['eZIdmlTypeWarningAdded'] = true;
            }
        }
    }

    function customObjectAttributeHTTPAction( $http, $action, $contentObjectAttribute, $parameters )
    {
        $params = explode( '-', $action );
        $base = 'ContentObjectAttribute';
        switch ( $params[0] )
        {
            case 'add_source_node_browse':
                $module = $parameters['module'];
                $redirectionURI = $redirectionURI = $parameters['current-redirection-uri'];
                    
                eZContentBrowse::browse( array( 'action_name' => 'AddSourceNode',
                                                'browse_custom_action' => array( 'name' => 'CustomActionButton[' . $contentObjectAttribute->attribute( 'id' ) . '_add_source_node]',
                                                                                 'value' => $contentObjectAttribute->attribute( 'id' ) ),
                                                'from_page' => $redirectionURI,
                                                'cancel_page' => $redirectionURI,
                                                'persistent_data' => array( 'HasObjectInput' => 0 ) ), $module );
                break;
            
            case 'add_source_node':
                if ( $http->hasPostVariable( 'SelectedNodeIDArray' ) )
                {
                    if ( !$http->hasPostVariable( 'BrowseCancelButton' ) )
                    {
                        $selectedNodeIDArray = $http->postVariable( 'SelectedNodeIDArray' );
                        $idmlPackage = new eZIdmlPackageHandler( $contentObjectAttribute );
                        $idmlPackage->addSourceNode( $selectedNodeIDArray );
                        $contentObjectAttribute->setContent(  $idmlPackage->idml );
                        $contentObjectAttribute->store();
                    }
                }
                break;
            
            case 'add_idml_file':

                eZIdmlType::checkFileUploads();
                $httpFileName = $base . "_data_idmlname_" . $contentObjectAttribute->attribute( "id" );
                $idmlPackage = new eZIdmlPackageHandler( $contentObjectAttribute );
                if ( eZHTTPFile::canFetch( $httpFileName ) )
                {
                    $httpFile = eZHTTPFile::fetch( $httpFileName );
                    if ( $httpFile )
                    {
                        $idmlPackage->reset( true );
                        $idmlPackage->initializeFromHTTPFile( $httpFile );
                        $contentObjectAttribute->setContent(  $idmlPackage->idml );
                        $contentObjectAttribute->store();
                    }
                    else
                    {
                        eZIdmlPackageHandler::regenerateSVGFiles( $idmlPackage->idml );
                    }
                }

                break;
            
            case 'add_from_repository':
                
                if( isset( $params[1] ) )
                {
                    $originalContentObjectID = $params[1];
                    $originalContentObject = eZContentObject::fetch( $originalContentObjectID );
                    if ( $originalContentObject )
                    {
                        $originalDataMap = $originalContentObject->dataMap();
                        foreach( $originalDataMap as $attribute )
                        {
                            if ( $attribute->attribute( 'data_type_string' ) == 'ezidml' )
                            {
                                $idmlPackage = new eZIdmlPackageHandler( $contentObjectAttribute );
                                $repositoryContentObjectAttribute = $attribute;
                                $repositoryIdmlDoc = $repositoryContentObjectAttribute->content();
                                $idmlPackage->reset( true );
                                $idmlPackage->initializeFromFile( $repositoryIdmlDoc->attribute( 'idml_file' ) );
                                $contentObjectAttribute->setContent(  $idmlPackage->idml );
                                $contentObjectAttribute->store();                               
                            }
                        }
                    }
                }
                
                break;
            
            default:
            break;
        }
    }

    function onPublish( $contentObjectAttribute, $contentObject, $publishedNodes )
    {
        $idmlPackage = new eZIdmlPackageHandler( $contentObjectAttribute );
        $idmlPackage->idml->removeProcessed();
        
        foreach ( array_keys( $publishedNodes ) as $publishedNodeKey )
        {
            $publishedNode = $publishedNodes[$publishedNodeKey];
            if ( $publishedNode->attribute( 'is_main' ) )
            {
                $mainNode = $publishedNode;
                break;
            }
        }
        if ( $mainNode )
        {
            if ( !$idmlPackage->idml->hasAttribute( 'source_node_id' ) )
            {
                $idmlPackage->addSourceNode( $mainNode->attribute( 'node_id' ) );
            }
        }
        
        if( $mainNode && eZIdmlType::$create_or_update_ez_contents )
        {
            eZIdmlImporter::createOrUpdateEzContents( $idmlPackage, $mainNode->attribute( 'node_id' ) );
        }
        
        eZIdmlPackageHandler::importEzContents( $idmlPackage->idml );
        $contentObjectAttribute->setContent(  $idmlPackage->idml );
        $contentObjectAttribute->store();
        return true;
    }

    function isIndexable()
    {
        return true;
    }

    function toString( $contentObjectAttribute )
    {
        return $contentObjectAttribute->attribute( 'data_text' );
    }

    function fromString( $contentObjectAttribute, $string )
    {
        $string = explode( '|', $string );
        
        //(string)filename|(int)$sourceNodeID|(bool)<$createOrUpdate>
        #eZDebug::writeNotice( $string, __METHOD__ );
        
        $fileName = $string[0];
        $sourceNodeID = isset( $string[1] ) ? $string[1] : false;
        $createOrUpdate = isset( $string[2] ) ? $string[2] : false;
        
        $idmlPackage = new eZIdmlPackageHandler( $contentObjectAttribute );
        
        $idmlPackage->initializeFromFile( $fileName );
        
        if ( $sourceNodeID )
        {
            //eZDebug::writeNotice( 'Adding source node #' . $sourceNodeID, __METHOD__ );
            $idmlPackage->addSourceNode( $sourceNodeID );
            $contentObjectAttribute->setContent(  $idmlPackage->idml );
            $contentObjectAttribute->store();
        }
        
        if ( $createOrUpdate )
        {
            //eZDebug::writeNotice( 'Create eZ contents', __METHOD__ );
            eZIdmlType::$create_or_update_ez_contents = true;   
        }
        
        $contentObjectAttribute->setContent(  $idmlPackage->idml );
        $contentObjectAttribute->store();
        return;
    }

    function serializeContentObjectAttribute( $package, $objectAttribute )
    {
        $node = $this->createContentObjectAttributeDOMNode( $objectAttribute );

        $dom = new DOMDocument( '1.0', 'utf-8' );
        $success = $dom->loadXML( $objectAttribute->attribute( 'data_text' ) );

        $importedRoot = $node->ownerDocument->importNode( $dom->documentElement, true );
        $node->appendChild( $importedRoot );

        return $node;
    }

    function unserializeContentObjectAttribute( $package, $objectAttribute, $attributeNode )
    {
        $rootNode = $attributeNode->childNodes->item( 0 );
        $xmlString = $rootNode ? $rootNode->ownerDocument->saveXML( $rootNode ) : '';
        $objectAttribute->setAttribute( 'data_text', $xmlString );
    }
    

    function deleteStoredObjectAttribute( $contentObjectAttribute, $version = null )
    {
        if ( $version === null )
        {
            $contentObject = eZContentObject::fetch( $contentObjectAttribute->attribute( 'contentobject_id' ) );
            $allVersions = $contentObject->versions();
            $languages = eZContentLanguage::fetchList();
            foreach ( $allVersions as $version )
            {
                foreach( $languages as $language )
                {
                    $dataMap = $version->contentObjectAttributes( $language->attribute( 'locale' ) );
                    foreach ( $dataMap as $attribute )
                    {
                        if ( $attribute->attribute( 'data_type_string' ) == self::DATA_TYPE_STRING )
                        {
                            $idmlPackage = new eZIdmlPackageHandler( $attribute );
                            $idmlPackage->reset();
                        }
                    }
                }
            }    
        }
        else
        {
            $idmlPackage = new eZIdmlPackageHandler( $contentObjectAttribute );
            $idmlPackage->reset( true );
        }
    }

}

eZDataType::register( eZIdmlType::DATA_TYPE_STRING, "ezidmltype" );
?>
