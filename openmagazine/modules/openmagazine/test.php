<?php
/**
 * File containing the test files.
 *
 * @version //autogen//
 * @copyright //autogen//
 * @license //autogen//
 * @author //autogen//
 */

$Module = $Params["Module"];

if ( isset( $Params['NodeID'] ) )
{
    $nodeID = $Params['NodeID'];
}
else
{
    $nodeID = 219;
}

if ( isset( $Params['Attribute'] ) )
{
    $Attribute = $Params['Attribute'];
}
else
{
    $Attribute = 'body';
}

$node = eZContentObjectTreeNode::fetch($nodeID);
if ( $node )
{
    $dataMap = $node->dataMap();
    $body = $dataMap[$Attribute];
    
    
    
    $res = eZTemplateDesignResource::instance();
    $res->setKeys( array( array( 'layout', 'idml' ) ) );
    print_r(  htmlentities( $body->content()->XMLData ));
    echo '<hr/><pre>';
    $body->content()->XMLOutputHandler = new eZIdmlXMLOutput( $body->content()->XMLData, false, $body->content()->ContentObjectAttribute );
    $body->content()->XMLOutputHandler->setDebug( true );
    print_r(  htmlentities( $body->content()->attribute('output')->outputText() ));
    echo '</pre>';
}
else
{
    echo 'Il nodo non esiste';
}
eZDisplayDebug();
eZExecution::cleanExit();
?>
