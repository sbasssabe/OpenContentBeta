<?php
/**
 * File containing the eZIdmlFileImport class.
 *
 * @version //autogen//
 * @copyright //autogen//
 * @license //autogen//
 * @author //autogen//
 */

class eZIdmlFileImport
{
    public $currentUserID;
    
    function __construct()
    {
        $currentUser = eZUser::currentUser();
        $this->currentUserID  = $currentUser->id();  
    }
    
    function import( $file, $placeNodeID, $originalFileName, $upload = null, $params = array() )
    {
        $idmlINI = eZINI::instance( 'idml.ini' );
        
        $importClassIdentifier = $idmlINI->variable( 'FileImport', 'DefaultImportClass' );
        $class = eZContentClass::fetchByIdentifier( $importClassIdentifier );
        
        $place_node = eZContentObjectTreeNode::fetch( $placeNodeID );
        if ( !is_object( $place_node ) )
        {
            $locationOK = false;

            if ( $upload !== null )
            {
                $parentNodes = false;
                $parentMainNode = false;
                $locationOK = $upload->detectLocations( $importClassIdentifier, $class, $placeNodeID, $parentNodes, $parentMainNode );
            }

            if ( $locationOK === false || $locationOK === null )
            {
                eZDebug::writeError( 'Location not found for file ' . $file, __METHOD__ );
                return false;
            }

            $placeNodeID = $parentMainNode;
            $place_node = eZContentObjectTreeNode::fetch( $placeNodeID );
            
            $functionCollection = new eZContentFunctionCollection();
            $access = $functionCollection->checkAccess( 'create', $place_node, $importClassIdentifier, $place_node->attribute( 'class_identifier' ) );

            if ( ! ( $access['result'] ) )
            {
                eZDebug::writeError( 'User can not create content for file ' . $file, __METHOD__ );
                return false;
            }
        }
        
        $place_object = $place_node->attribute( 'object' );
        $sectionID = $place_object->attribute( 'section_id' );

        $creatorID = $this->currentUserID;
        $parentNodeID = $placeNodeID;

        if ( !is_object( $class ) )
        {
            eZDebug::writeError( $importClassIdentifier . ' class does not exists', __METHOD__ );
            return false;
        }

        $object = $class->instantiate( $creatorID, $sectionID );

        $nodeAssignment = eZNodeAssignment::create( array(
                                                         'contentobject_id' => $object->attribute( 'id' ),
                                                         'contentobject_version' => $object->attribute( 'current_version' ),
                                                         'parent_node' => $parentNodeID,
                                                         'is_main' => 1
                                                         )
                                                     );
        $nodeAssignment->store();

        $version = $object->version( 1 );
        $version->setAttribute( 'modified', eZDateTime::currentTimeStamp() );
        $version->setAttribute( 'status', eZContentObjectVersion::STATUS_DRAFT );
        $version->store();
        $dataMap = $object->dataMap();
            
        $contentObjectID = $object->attribute( 'id' );
        $objectName = basename( $originalFileName );

        $objectName = str_replace( ".idml", "", $objectName );
        $objectName = ucfirst( str_replace( "_", " ", $objectName ) );
        
        $nameAttribute = $idmlINI->variable( 'FileImport', 'DefaultImportNameAttribute' );
        $dataMap[ $nameAttribute ]->setAttribute( 'data_text', $objectName );
        $dataMap[ $nameAttribute ]->store();

        $idmlAttribute = $idmlINI->variable( 'FileImport', 'DefaultImportIdmlAttribute' );
        
        $fromString = realpath( $file );
        
        if ( isset( $params[ 'source_node_id' ] ) )
            $fromString .=  '|' . $params['source_node_id'];
        else
            $fromString .= '|false';
        
        if ( isset( $params[ 'create_or_upload' ] ) )
            $fromString .=  '|1';
        else
            $fromString .= '|0';
        
        $dataMap[ $idmlAttribute ]->fromString( $fromString );
        $dataMap[ $idmlAttribute ]->store();

        $operationResult = eZOperationHandler::execute( 'content', 'publish', array( 'object_id' => $contentObjectID,
                                                                                         'version' => $version->attribute( 'version' ) ) );
        $mainNode = $object->attribute( 'main_node' );

        $importResult = array();
        $importResult['Object'] = $object;
        $importResult['MainNode'] = $mainNode;
        $importResult['URLAlias'] = $mainNode->attribute( 'url_alias' );
        $importResult['NodeName'] = $mainNode->attribute( 'name' );
        $importResult['ClassIdentifier'] = $importClassIdentifier;
        
        return $importResult;
        
    }
}
?>
