<?php
/**
 * File containing the sort files.
 *
 * @version //autogen//
 * @copyright //autogen//
 * @license //autogen//
 * @author //autogen//
 */

$nodeID = isset( $Params['NodeID'] ) ? (int) $Params['NodeID'] : 0;
$Result = array();
$node   = false;
$viewParameters = array( 'offset' => $Params['Offset'],
                         'redirect' => $Params['RedirectURL'],
                         'year' => $Params['Year'],
                         'month' => $Params['Month'],
                         'day' => $Params['Day'],
                         'namefilter' => false );

if ( isset( $Params['UserParameters'] ) )
{
    $viewParameters = array_merge( $viewParameters, $Params['UserParameters'] );
}

if ( $nodeID !== 0 )
{
    $node = eZContentObjectTreeNode::fetch( $nodeID );
}

if ( !$node instanceof eZContentObjectTreeNode )
{
    $Result['content'] = ezi18n( 'design/standard/websitetoolbar/sort', 'Invalid or missing parameter: %parameter', null, array( '%parameter' => 'NodeID' ) );
    return $Result;
}

$tpl = eZTemplate::factory();
$tpl->setVariable( 'node', $node );
$tpl->setVariable( 'view_parameters', $viewParameters );
$tpl->setVariable( 'persistent_variable', false );

$parents = $node->attribute( 'path' );

$path = array();
$titlePath = array();
foreach ( $parents as $parent )
{
    $path[] = array( 'text' => $parent->attribute( 'name' ),
                     'url' => '/content/view/full/' . $parent->attribute( 'node_id' ),
                     'url_alias' => $parent->attribute( 'url_alias' ),
                     'node_id' => $parent->attribute( 'node_id' ) );
}

$titlePath = $path;
$path[] = array( 'text' => $node->attribute( 'name' ),
                 'url' => false,
                 'url_alias' => false,
                 'node_id' => $node->attribute( 'node_id' ) );

$titlePath[] = array( 'text' => $node->attribute( 'name' ),
                      'url' => false,
                      'url_alias' => false );

$tpl->setVariable( 'node_path', $path );

$Result['content'] = $tpl->fetch( 'design:openmagazine/sort.tpl' );
$Result['path'] = $path;
$Result['title_path'] = $titlePath;

$contentInfoArray = array();
$contentInfoArray['persistent_variable'] = $tpl->variable( 'persistent_variable' );
$Result['content_info'] = $contentInfoArray;

return $Result;

?>
