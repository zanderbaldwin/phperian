<?php

    namespace PHPerian;

    use \PHPerian\Exception as Exception;

    /**
     * Request Base Class
     *
     * The base class for all service calls to Experian.
     *
     * @package     PHPerian
     * @category    Library
     * @author      Zander Baldwin <mynameiszanders@gmail.com>
     * @license     MIT/X11 <http://j.mp/mit-license>
     * @link        https://github.com/mynameiszanders/phperian/blob/develop/src/PHPerian/Request.php
     */
    class Request
    {

        /**
         * @var array $blocks
         */
        protected $blocks = array();

        /**
         * Constructor Method
         *
         * @access public
         * @return void
         */
        public function __construct() {}

        /**
         * Magic Method: Call
         *
         * If a method to create a new object has been called, attempt to find
         * the class and initialise it with the parameters passed.
         * Obviously, if the method does not exist, throw an exception.
         *
         * @access public
         * @throws \Nosco\Exception
         * @return object
         */
        public function __call($method, $arguments)
        {
            // This magic method is to catch any calls to "magic" methods that create and return a new instance of a
            // Request sub-class. So, make sure that the method keyword is "create", and that the rest of the method
            // string identifies the class to initiate.
            if(preg_match('/^create([A-Z].*)$/', $method, $matches)) {
                // Make sure that the class specified in the method exists as a Request sub-class.
                $class = '\\' . __CLASS__ . '\\Partial\\' . $matches[1];
                if(class_exists($class)) {
                    // It does? Great! Create a new instance based on the arguments passed and return it.
                    $reflection = new \ReflectionClass($class);
                    $instance = $reflection->newInstanceArgs($arguments);
                    $this->blocks[] = $instance->id();
                    return $instance;
                }
            }
            // If the method called was invalid (eg, it didn't map to a Request sub-class) throw an exception.
            throw new Exception('Call to undefined method "' . $method . '" on class "' . __CLASS__ . '".');
        }

        /**
         * Shortcut: Verbose
         *
         * @access public
         * @return void
         */
        public function verbose()
        {
            \PHPerian\Request\Partial::verbose();
        }

        /**
         * Shortcut: Silent
         *
         * @access public
         * @return void
         */
        public function silent()
        {
            \PHPerian\Request\Partial::silent();
        }

        /**
         * Generate XML
         *
         * @access public
         * @return string
         */
        public function xml()
        {
            $xml = '';
            foreach($this->blocks as $block_id) {
                $block = \PHPerian\Request\Partial::fetchById($block_id);
                $block_class = explode('\\', get_class($block));
                $block_class = end($block_class);
                $xml .= '<' . $block_class . '>' . $block->generateXML() . '</' . $block_class . '>';
            }
            return $this->soapWrap($xml);
        }

        // THIS IS A TEMPORARY METHOD TO WRAP THE XML IN A SOAP REQUEST.
        // THIS NEEDS REWRITING.
        // DO NOT PUSH THIS INTO PRODUCTION.
        // THAT WOULD BE, LIKE, REAL BAD.
        public function soapWrap($xml)
        {
            return '<?xml version="1.0" encoding="utf-8"?><soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/en'
                .'velope/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLS'
                .'chema" xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd"'
                .' xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd"><soap'
                .':Header><wsse:Security><wsse:BinarySecurityTokenValueType="ExperianWASP"EncodingType="wsse:Base64Bina'
                .'ry"wsu:Id="SecurityToken">{{BinarySecurityToken}}</wsse:BinarySecurityToken></wsse:Security></soap:He'
                .'ader><soap:Body><ns2:Interactive xmlns:ns2="http://www.uk.experian.com/experian/wbsv/peinteractive/v1'
                .'00"><ns1:Root xmlns:ns1="http://schemas.microsoft.com/BizTalk/2003/Any"><ns0:Input xmlns:ns0="http://'
                .'schema.uk.experian.com/experian/cems/msgs/v1.7/ConsumerData">'
                . $xml
                . '</ns0:Input></ns1:Root></ns2:Interactive></soap:Body></soap:Envelope>';
        }







    }