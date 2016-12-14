<?php namespace Granada\Orm;


    /**
     * A class to handle str_replace operations that involve quoted strings
     * @example IdiormString::str_replace_outside_quotes('?', '%s', 'columnA = "Hello?" AND columnB = ?');
     * @example IdiormString::value('columnA = "Hello?" AND columnB = ?')->replace_outside_quotes('?', '%s');
     * @author Jeff Roberson <ridgerunner@fluxbb.org>
     * @author Simon Holywell <treffynnon@php.net>
     * @link http://stackoverflow.com/a/13370709/461813 StackOverflow answer
     */
    class Str {
        protected $subject;
        protected $search;
        protected $replace;

        /**
         * Get an easy to use instance of the class
         * @param string $subject
         * @return \self
         */
        public static function value($subject) {
            return new self($subject);
        }

        /**
         * Shortcut method: Replace all occurrences of the search string with the replacement
         * string where they appear outside quotes.
         * @param string $search
         * @param string $replace
         * @param string $subject
         * @return string
         */
        public static function str_replace_outside_quotes($search, $replace, $subject) {
            return self::value($subject)->replace_outside_quotes($search, $replace);
        }

        /**
         * Set the base string object
         * @param string $subject
         */
        public function __construct($subject) {
            $this->subject = (string) $subject;
        }

        /**
         * Replace all occurrences of the search string with the replacement
         * string where they appear outside quotes
         * @param string $search
         * @param string $replace
         * @return string
         */
        public function replace_outside_quotes($search, $replace) {
            $this->search = $search;
            $this->replace = $replace;
            return $this->_str_replace_outside_quotes();
        }

        /**
         * Validate an input string and perform a replace on all ocurrences
         * of $this->search with $this->replace
         * @author Jeff Roberson <ridgerunner@fluxbb.org>
         * @link http://stackoverflow.com/a/13370709/461813 StackOverflow answer
         * @return string
         */
        protected function _str_replace_outside_quotes(){
            $re_valid = '/
                # Validate string having embedded quoted substrings.
                ^                           # Anchor to start of string.
                (?:                         # Zero or more string chunks.
                  "[^"\\\\]*(?:\\\\.[^"\\\\]*)*"  # Either a double quoted chunk,
                | \'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*\'  # or a single quoted chunk,
                | [^\'"\\\\]+               # or an unquoted chunk (no escapes).
                )*                          # Zero or more string chunks.
                \z                          # Anchor to end of string.
                /sx';
            if (!preg_match($re_valid, $this->subject)) {
                throw new IdiormStringException("Subject string is not valid in the replace_outside_quotes context.");
            }
            $re_parse = '/
                # Match one chunk of a valid string having embedded quoted substrings.
                  (                         # Either $1: Quoted chunk.
                    "[^"\\\\]*(?:\\\\.[^"\\\\]*)*"  # Either a double quoted chunk,
                  | \'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*\'  # or a single quoted chunk.
                  )                         # End $1: Quoted chunk.
                | ([^\'"\\\\]+)             # or $2: an unquoted chunk (no escapes).
                /sx';
            return preg_replace_callback($re_parse, array($this, '_str_replace_outside_quotes_cb'), $this->subject);
        }

        /**
         * Process each matching chunk from preg_replace_callback replacing
         * each occurrence of $this->search with $this->replace
         * @author Jeff Roberson <ridgerunner@fluxbb.org>
         * @link http://stackoverflow.com/a/13370709/461813 StackOverflow answer
         * @param array $matches
         * @return string
         */
        protected function _str_replace_outside_quotes_cb($matches) {
            // Return quoted string chunks (in group $1) unaltered.
            if ($matches[1]) return $matches[1];
            // Process only unquoted chunks (in group $2).
            return preg_replace('/'. preg_quote($this->search, '/') .'/',
                $this->replace, $matches[2]);
        }
    }
