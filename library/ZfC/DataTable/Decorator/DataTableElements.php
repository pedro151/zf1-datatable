<?php

/** ZfC_DataTable_Decorator_Abstract */

class ZfC_DataTable_Decorator_DataTableElements extends ZfC_DataTable_Decorator_Abstract
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
        if (!$datatable instanceof ZfC_DataTable) {
            return $content;
        }

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
