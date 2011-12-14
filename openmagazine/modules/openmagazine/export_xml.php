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
$module = $Params["Module"];
$NodeID = $Params['NodeID'];

$selectedNodeIDArray = array();
$selectedNodeIDString = $http->hasPostVariable( "selectedNodeIDString" ) ? $http->postVariable( "selectedNodeIDString" ) : false;

if ( $http->hasPostVariable( "BrowseButton" ) )
{
    eZContentBrowse::browse( array( 'action_name' => 'OpenMagazineXmlExportPlace',
                                    'description_template' => 'design:openmagazine/xml_export_browse_place.tpl',
                                    'content' => array(),
                                    'from_page' => '/openmagazine/export_xml/',
                                    'cancel_page' => '/openmagazine/export_xml/',
                                    'persistent_data' => array( 'selectedNodeIDString' => $selectedNodeIDString ) ),
                                    $module );
    return;
}

if ( $module->isCurrentAction( 'OpenMagazineXmlExportPlace' ) )
{
    $selectedNodeIDArray = eZContentBrowse::result( 'OpenMagazineXmlExportPlace' );
}

if ( $selectedNodeIDString )
{
    $selectedNodeIDArray = array_merge( $selectedNodeIDArray, explode( '-', $selectedNodeIDString ) );
}

if ( $http->hasPostVariable( "RemoveButton" ) )
{
    $removeNodeIDArray = $http->hasPostVariable( "RemoveNodeIDArray" ) ? $http->postVariable( "RemoveNodeIDArray" ) : false;
    if ( $removeNodeIDArray )
    {
        $selectedNodeIDArray = array_diff( $selectedNodeIDArray, $removeNodeIDArray );
    }
}

if ( $http->hasPostVariable( "ExportButton" ) )
{
    $OpenMagazinePriority = $http->hasPostVariable( "OpenMagazinePriority" ) ? $http->postVariable( "OpenMagazinePriority" ) : false;
    $http->setSessionVariable( 'OpenMagazineSelectedNodeIDArray', $selectedNodeIDArray );
    $http->setSessionVariable( 'OpenMagazinePriority', $OpenMagazinePriority );
    $module->redirectToView( 'do_export_xml' );
}

$tpl->setVariable( 'selectedNodes', array_unique( $selectedNodeIDArray ) );

$Result = array();
$Result['content'] = $tpl->fetch( "design:openmagazine/export_xml.tpl" );
$Result['path'] = array( array( 'url' => '/openmagazine/export_xml/',
                                'text' => ezpI18n::tr( 'extension/openmagazine', "Export in OpenMagazine XML format" ) ) );

?>
