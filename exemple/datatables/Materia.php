<?php

/**
 * Admin_DataTable_Materia
 *
 * Cria lista de materias com descricao e modulo
 *
 * @author Pedro Alarcao
 * @return Cgmi_DataTable
 */
class Admin_DataTable_Materia extends ZfComplement_DataTable
{

    public function init ()
    {
        $this->setName ( 'Materias' );
        $this->setAjax ( 'materia/ajax' );

        # CAMPO NOME
        $no_materia = $this->createElement ( 'text' , 'no_materia' )
                           ->setLabel ( 'Materia' );

        # CAMPO DESCRICAO
        $ds_materia = $this->createElement ( 'text' , 'ds_materia' )
                           ->setLabel ( 'Descrição' );

        # BUTAO DE MODULO
        $modulo = $this->createElement ( 'button' , 'modulos' )
                       ->setValue ( "Modulos" )
                        ->setUrl(array('module'=>'modulo','controller'=>'modulos'))
                       ->setOptions (
                           array ( 'paramJs' => array ( 'ds_materia' , 'no_materia' ) )
                       );

        # BUTAO DE LIMPAR
        $remover = $this->createElement ( 'button' , 'remover' )
                        ->setValue ( "Remover" )
                        ->setOptions (
                            array ( 'paramJs' => array ( 'ds_materia' ) )
                        )
                        ->setUrl(array('controller'=>'remover'));

        $this->addElements (
            array (
                $no_materia ,
                $ds_materia ,
                $modulo ,
                $remover
            )
        );
    }

}