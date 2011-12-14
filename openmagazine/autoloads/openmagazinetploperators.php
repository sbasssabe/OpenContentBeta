<?php
/**
 * File containing the OpenMagazineTplOperators class.
 *
 * @version //autogen//
 * @copyright //autogen//
 * @license //autogen//
 * @author //autogen//
 */

class OpenMagazineTplOperators
{

    function operatorList()
    {
        return array(
                     'washxml',
                     'washxmlcomment',
                     'washxmlcdata',
                     'httpcharset',
                     'httpheader',
                     'check_char_length'
                     );
    }

    function namedParameterPerOperator()
    {
        return true;
    }

    function namedParameterList()
    {
        return array( 'check_char_length' => array(
                            'original_char_length' => array( 'type' => 'string',
                                              'required' => true,
                                              'default' => false ),
                            'char_length' => array( 'type' => 'string',
                                              'required' => true,
                                              'default' => false ),
                            'attribute_identifier' => array( 'type' => 'string',
                                              'required' => false,
                                              'default' => false ),
                            'xmltag' => array( 'type' => 'mixed',
                                              'required' => false,
                                              'default' => false ) ) );
    }

    function modify( $tpl, $operatorName, $operatorParameters, &$rootNamespace, &$currentNamespace, &$operatorValue, &$namedParameters )
    {
        switch ( $operatorName )
        {
            case 'washxml':
            {
                $operatorValue = str_replace( array( '&', '"', "'", '<', '>', "\n", "\n" ), array( '&amp;', '&quot;', '&apos;', '&lt;', '&gt;', '<br />', '<br/>' ), $operatorValue );
            } break;
            case 'washxmlcomment':
            {
                // in xml comments the -- string is not permitted
                $operatorValue = str_replace( '--', '_-', $operatorValue );
            } break;
            case 'washxmlcdata':
            {
                /// @todo
            } break;
            case 'httpcharset':
            {
                $operatorValue = eZTextCodec::httpCharset();
            } break;
            case 'httpheader':
            {
                header( $operatorValue );
                $operatorValue = '';
            } break;
            case 'check_char_length':
            {
                $operatorValue = eZIdmlContent::checkCharLength( $namedParameters );
            } break;
        }
    }
}

?>
