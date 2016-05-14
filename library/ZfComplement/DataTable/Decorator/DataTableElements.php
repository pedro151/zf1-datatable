<?php

/** ZfComplement_DataTable_Decorator_Abstract */

class ZfComplement_DataTable_Decorator_DataTableElements extends ZfComplement_DataTable_Decorator_Abstract
{

    /**
     * Render form elements
     *
     * @param  string $content
     * @return string
     */
    public function render($content)
    {
        $datatable    = $this->getElement();
        if (!$datatable instanceof ZfComplement_DataTable) {
            return $content;
        }

        $elementContent = '';
        $separator      = $this->getSeparator();
        $translator     = $datatable->getTranslator();
        $items          = array();
        $view           = $datatable->getView();
        foreach ($datatable as $item) {
            $item->setView($view);

            // Set translator
            if (!$item->hasTranslator()) {
                $item->setTranslator($translator);
            }

            $items[] = $item->render();

        }

        return $items;
    }
}
