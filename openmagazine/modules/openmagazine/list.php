<?php
/**
 * File containing the list files.
 *
 * @version //autogen//
 * @copyright //autogen//
 * @license //autogen//
 * @author //autogen//
 */

$module = $Params['Module'];
$ini = $ini = eZINI::instance( 'openmagazine.ini' );
$http = eZHTTPTool::instance();

$export_view = isset($Params['ExportView']) ? $Params['ExportView'] : 'default';
$export_content_type = isset($Params['ContentType']) ? $Params['ContentType'] : 'json';
$node_id = isset($Params['NodeID']) ? $Params['NodeID'] : 0;

$magazine = false;
$result = array();

$rootList = false; 

if ( $node_id == 'last'  ) 
{
	$last_magazine_params = array( 
		'MainNodeOnly' => true,
		'Limit' => 1, 
		'ClassFilterType' => 'include',
		'ClassFilterArray' => $ini->variable( 'ExportClassSettings', 'MagazineSectionClassIdentifier' ),
		'SortBy' => array( 'published', false ) 
	);

	$parent_node_id = ( $ini->variable( 'ExportClassSettings', 'MagazineParentNode' ) ) ? $ini->variable( 'ExportClassSettings', 'MagazineParentNode' ) : 2;	
	$last_magazine = eZContentObjectTreeNode::subTreeByNodeID( $last_magazine_params, $parent_node_id );
	
	if ( $last_magazine ) 
	{
		$magazine = $last_magazine[0];
	} 
	
} 
elseif ( is_numeric( $node_id ) && $node_id > 0 )
{
	$magazine = eZContentObjectTreeNode::fetch( intval( $node_id ) );
}
elseif ( $node_id == 0 )
{
   
   $magazine_params = array( 
		'MainNodeOnly' => true,
		'ClassFilterType' => 'include',
		'ClassFilterArray' => $ini->variable( 'ExportClassSettings', 'MagazineContainerClassIdentifier' ),
		'SortBy' => array( 'published', false ) 
	);

	$parent_node_id = ( $ini->hasVariable( 'ExportClassSettings', 'MagazineParentNode' ) ) ? $ini->variable( 'ExportClassSettings', 'MagazineParentNode' ) : 2;	
	
    $magazines = eZContentObjectTreeNode::subTreeByNodeID( $magazine_params, $parent_node_id );
    
	if ( $magazines ) 
	{
		$magazine = $magazines;
        $rootList = true;
	} 
}

$result = array( 'data' => array(), 'errors' => array(), 'container' => $magazine, 'export_view' => $export_view );

if ( $rootList )
    $export_view = 'default';

OpenMagazineExportHandler::exec( 'OpenMagazine_' . $export_view . '_ExportHandler', 'exportList', $result );

if ( isset( $result['container'] ) )
    unset( $result['container'] );

if ( method_exists( 'OpenMagazineContentTypeHandler', $export_content_type ) )
{
	call_user_func( array( 'OpenMagazineContentTypeHandler', $export_content_type ), $result); 
}
else
{
	$result ['errors'][] = 'Content type not implemented';
	call_user_func( array( 'OpenMagazineContentTypeHandler', 'debug' ), $result); 
}

eZExecution::cleanExit();
?>
