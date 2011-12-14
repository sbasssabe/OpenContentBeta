<?php
/**
 * File containing the export xml files.
 *
 * @version //autogen//
 * @copyright //autogen//
 * @license //autogen//
 * @author //autogen//
 */

$http = eZHTTPTool::instance();
$tpl = eZTemplate::factory();
$module = $Params['Module'];
$nodeID = $Params['NodeID'];
$selectedNodeIDArray = $nodeID ? array( $nodeID ) : array();
$OpenMagazinePriority = array();

$res = eZTemplateDesignResource::instance();
$res->setKeys( array( array( 'layout', 'openmagazinexml' ) ) );

if ( $http->hasSessionVariable( 'OpenMagazineSelectedNodeIDArray' ) )
{
    $selectedNodeIDArray = array_merge( $selectedNodeIDArray, $http->sessionVariable( 'OpenMagazineSelectedNodeIDArray' ) );
    $http->removeSessionVariable( 'OpenMagazineSelectedNodeIDArray' );
}

if ( $http->hasSessionVariable( 'OpenMagazinePriority' ) )
{
    $OpenMagazinePriority = $http->sessionVariable( 'OpenMagazinePriority' );
    array_walk($OpenMagazinePriority, 'intval');
    $http->removeSessionVariable( 'OpenMagazinePriority' );
}

if ( empty( $selectedNodeIDArray ) )
    $module->redirectToView( 'export_xml' );

$tpl->setVariable( "openmagazine_priority", $OpenMagazinePriority );

if ( count( $selectedNodeIDArray) == 1 && $nodeID !== false )
{
    $node = eZContentObjectTreeNode::fetch( $selectedNodeIDArray[0] );
    $tpl->setVariable( "node", $node );
    $name = 'node-' . $node->attribute('node_id') . '.openmagazine';
}
else
{
    $nodes = eZContentObjectTreeNode::fetch( $selectedNodeIDArray );
    $tpl->setVariable( "nodes", $nodes );
    $name = 'selected_nodes.openmagazine';
}

$ini = eZINI::instance( 'site.ini' );
$var = $ini->variable( 'FileSettings','VarDir' );
$varPath = $var . '/storage/original/openmagazine/';
if ( !is_dir( $varPath ) )
{
    eZDir::mkdir( $varPath, false, true );
}
$filePath = $varPath . $name . '.xml';

$moduleResult = array();
$moduleResult['content'] = $tpl->fetch( "design:node/view/openmagazinexml.tpl" );
$tpl->setVariable( "module_result", $moduleResult );
$templateResult = $tpl->fetch( "design:openmagazinexml_pagelayout.tpl" );

//@DEBUG
//print_r($templateResult);die();

eZFile::create( $filePath, false, $templateResult );

eZFile::download( $filePath, true, $name . '.xml' );

if ( $nodeID !== false )
    $module->redirect( 'content', 'view', array( 'full', $nodeID ) );
else
    $module->redirectToView( 'export_xml' );
