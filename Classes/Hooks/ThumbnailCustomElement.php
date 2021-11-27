<?php
declare(strict_types=1);
namespace Kitodo\Dlf\Hooks;

use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;

class ThumbnailCustomElement extends AbstractFormElement
{
    public function render()
    {
        // Custom TCA properties and other data can be found in $this->data, for example the above
        // parameters are available in $this->data['parameterArray']['fieldConf']['config']['parameters']
        $result = $this->initializeResultArray();
        if (!empty($this->data['databaseRow']['thumbnail'])) {
            $result['html'] = '<img alt="Thumbnail" title="" src="' . $this->data['databaseRow']['thumbnail'] . '" />';
        } else {
            $result['html'] = '';
        }
        return $result;
    }
}