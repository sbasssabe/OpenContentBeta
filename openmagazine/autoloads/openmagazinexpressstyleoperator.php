<?php
/**
 * File containing the OpenMagazineXpressStyleOperator class.
 *
 * @version //autogen//
 * @copyright //autogen//
 * @license //autogen//
 * @author //autogen//
 */

class OpenMagazineXpressStyleOperator
{
    /*!
      Costruttore, come impostazione predefinita non fa nulla.
    */
    function OpenMagazineXpressStyleOperator()
    {
    }

    /*!
     \return an array with the template operator name.
    */
    function operatorList()
    {
        return array( 'xpress_style', 'xpress_style_name' );
    }

    /*!
     \return true to tell the template engine that the parameter list exists per operator type,
             this is needed for operator classes that have multiple operators.
    */
    function namedParameterPerOperator()
    {
        return true;
    }

    /*!
     See eZTemplateOperator::namedParameterList
    */
    function namedParameterList()
    {
        return array( 'xpress_style' => array( 'first_param' => array( 'type' => 'string',
                                                                  'required' => false,
                                                                  'default' => 'default string to operator' ) ),
					'xpress_style_name' => array( 'first_param' => array( 'type' => 'string',
                                                                  'required' => false,
                                                                  'default' => 'default string to operator' ) ),
																  );
    }


    /*!
     Esegue la funzione PHP per la pulizia dell'operatore e modifica \a $operatorValue.
    */
    function modify( $tpl, $operatorName, $operatorParameters, $rootNamespace, $currentNamespace, &$operatorValue, $namedParameters, $placement )
    {
        $firstParam = $namedParameters['first_param'];

        switch ( $operatorName )
        {

			case 'xpress_style':
            {                				
				$operatorValue = $this->search_and_print_xpress_style( $operatorValue );
            } 
			break;

			case 'xpress_style_name':
            {                				
				$operatorValue = $this->search_style_name_from_attribute( $operatorValue );
            } 
			break;			
			
			
        }
    }		
	
	function search_style_name_from_attribute( $attribute )
	{
		return '@' . $attribute->attribute( 'contentclass_attribute_identifier' );
	}
	
	function search_and_print_xpress_style( $node_id )
	{
        return '';
    }
	
	
}

?>
