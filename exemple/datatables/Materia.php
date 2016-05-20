<?php

/**
 * Admin_DataTable_Materia
 *
 * Cria lista de materias com descricao e modulo
 *
 * @author Pedro Alarcao
 * @return Cgmi_DataTable
 */
class Default_DataTable_Materia extends ZfC_DataTable
{

    public function init ()
    {
        $this->setName ( 'Materias' );
        $this->setAjax ( 'materia/ajax' );
        $this->setMethod ( "POST" );

        # CAMPO ID DA MATERIA EM HIDDEN
        $idmateria = $this->createElement ( 'hidden' , 'idmateria' );

        # CAMPO NOME DA MATERIA
        $no_materia = $this->createElement ( 'text' , 'no_materia' )
                           ->setLabel ( 'Materia' );

        # CAMPO DESCRICAO
        $ds_materia = $this->createElement ( 'text' , 'ds_materia' )
                           ->setLabel ( 'Descrição' );

        # BUTAO DE ENTRAR
        $modulo = $this->createElement ( 'button' , 'modulos' )
                       ->setValue ( "Modulos" )
                       ->setUrl ( array (
                           'controller' => 'modulo' ,
                           'action'     => 'index'
                       ) )
                       ->setOptions (
                           array ( 'paramJs' => array ( 'ds_materia' ) )
                       );

        # BUTAO DE LIMPAR
        $remover = $this->createElement ( 'button' , 'remover' )
                        ->setValue ( "Remover" )
                        ->setOptions (
                            array ( 'paramJs' => array ( 'idmateria' ) )
                        )
                        ->setUrl ( array ( 'action' => 'remover' ) );

        $this->addElements (
            array (
                $no_materia ,
                $ds_materia ,
                $modulo ,
                $remover ,
                $idmateria
            )
        );

    }
}