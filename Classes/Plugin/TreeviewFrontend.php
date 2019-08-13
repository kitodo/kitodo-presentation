<?php
namespace Kitodo\Dlf\Plugin;

/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

/**
 * Plugin 'Treeview Frontend' for the 'dlf' extension
 *
 * @author Christopher Timm <timm@effective-webwork.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class TreeviewFrontend extends \Kitodo\Dlf\Common\AbstractPlugin {
    public $scriptRelPath = 'plugins/treeviewfrontend/class.tx_dlf_treeviewfrontend.php';

    /**
     * The main method of the PlugIn
     *
     * @access	public
     *
     * @param	string		$content: The PlugIn content
     * @param	array		$conf: The PlugIn configuration
     *
     * @return	string		Returns a div and javascript for jsTree
     */
    public function main($content, $conf)
    {

        $this->init($conf);

        $requestUri = $this->conf['requestUri'];
        $id = $this->conf['id'];
        $query = $this->conf['query'];
        $level = $this->conf['level'];
        $apiKey = $this->conf['apikey'];

        if (empty($query)) {
            $query = '*';
        }

        if (empty($level)) {
            $level = 0;
        }

        $script = "
            <div id=\"" . $id . "\" class=\"\"></div>

            <script>
            
                // ajax demo
                $('#" . $id . "').jstree({
                    'core' : {
                        'themes': {
                            'responsive': true
                        },
                        'data' : {
                            \"url\" : function (node) {
                                var level = node.id.split('#')[0];
                                var collection = node.id.split('#')[2];
                                var query = node.id.split('#')[1];
            
                                if (collection === \"\" || typeof collection == \"undefined\") {
                                    collection = \"\";
                                }
            
                                if (level === \"\") {
                                    level = " . $level . ";
                                } else {
                                    level = parseInt(level) + 1;
                                }
                                if (query === \"\") {
                                    query = '" . $query . "';
                                }
            
                                if (typeof level == \"number\") {
                                    return '" . $requestUri . "&tx_dlf[apikey]=" . $apiKey . "&tx_dlf[query]=' + query + '&tx_dlf[level]='+level+'&tx_dlf[collection]='+collection;
                                }
                            },
                            \"dataType\" : \"json\" // needed only if you do not supply JSON headers
                        }
                    }
                }).on(\"select_node.jstree\", function(e, data) {
                    if (data.node.a_attr.href != '') {
                        window.open(data.node.a_attr.href, '_blank').focus();
                    } else {
                        data.instance.toggle_node(data.node);   
                    }
                });
                
            </script>
        ";

        return $script;

    }
}
