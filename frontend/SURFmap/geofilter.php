<?php
    /******************************
     # geofilter.php [SURFmap]
     # Author: Rick Hofstede <r.j.hofstede@utwente.nl>
     # University of Twente, The Netherlands
     #
     # LICENSE TERMS: outlined in BSD-license.html
     *******************************/
    
    require_once("iso3166.php");
    
    $logicOperators = array('and', 'or'); // 'not' is also a logic operator, but should not be used here (although it is supported by this geofilter parser)
    $originOperators = array('src', 'dst');
    $geolocationOperators = array('country', 'region', 'city', 'ctry', 'rgn', 'cty');
    
    /*
     * Validates the provided object against the provided expression.
     * Parameters:
     *      object - single object (SURFmap geolocation style) to validate.
     * Return:
     *      True, if the expression validated to true against the object. Otherwise,
     *      false.
     */ 
    function eval_geo_filter ($object, $expression) {
        global $logicOperators, $originOperators, $geolocationOperators;
        
        $expression = trim($expression); // Removes unwanted chars from *beginning* and *end*
        $expression = str_replace("\n", "", $expression);
        $expression = str_replace("\r", "", $expression);
        
        if (empty($expression)) {
            return true;
        } else if (contains_logic_operator($expression) || contains_brackets($expression)) { // expression needs to be truncated
            $outerExpressions = get_outer_filter_expressions($expression); // returns array
            
            $result = null;
            $currentLogicOperator = null;
            $currentLogicNegationOperator = null;

            foreach ($outerExpressions as $outerExpression) {
                if (strcasecmp($outerExpression, "not") === 0) {
                    $currentLogicNegationOperator = true;
                } else if (isset($currentLogicNegationOperator) && $currentLogicNegationOperator === true) {
                    $result = perform_logic_negation(evaluateGeoFilter($object, $outerExpression));
                    $currentLogicNegationOperator = null;
                } else if (in_arrayi($outerExpression, $logicOperators)) { // logic operator
                    $currentLogicOperator = $outerExpression;
                } else if (isset($result)) { // second or later element of outer expression; $currentLogicOperator should therefore be set
                    if (strcasecmp($currentLogicOperator, "and") === 0) {
                        // If the first result in an 'and' operation is false, evaluation of the second expression can be skipped
                        if ($result) $result = perform_logic_AND(array($result, evaluateGeoFilter($object, $outerExpression)));
                    } else if (strcasecmp($currentLogicOperator, "or") === 0) { // logic OR
                        // If the first result in an 'or' operation is true, evaluation of the second expression can be skipped
                        if ($result === true) $result = perform_logic_OR(array($result, evaluateGeoFilter($object, $outerExpression)));
                    } else {
                        throw new GeoFilterException("Logic operator (and/or) is missing (near '$outerExpression')");
                    }
                    $currentLogicOperator = null;
                } else { // first element of outer expression
                    $result = evaluateGeoFilter($object, $outerExpression);
                }
            }
            unset($outerExpression);
            
            return $result;
        } else {
            $logicNegationOperator = contains_logic_negation_operator($expression);
            $originOperator = get_origin_operator($expression);
            $geolocationOperator = get_geolocation_operator($expression);
            
            if ($geolocationOperator === false) {
                throw new GeoFilterException("Geolocation operator (country/region/city/ctry/rgn/cty) is missing (near '$expression')");
            }

            $filterValue = get_filter_value($expression, $logicNegationOperator, $originOperator, $geolocationOperator);
            foreach ($geolocationOperators as $operator) {
                if (strpos($filterValue, $operator) !== false) {
                    throw new GeoFilterException("Logic operator (and/or) is missing (near '$expression')");
                }
            }
            unset($operator);

            if (strcasecmp($filterValue, "") === 0) {
                throw new GeoFilterException("Filter value is missing (near '$expression')");
            } else if (is_country_geolocation_operator($geolocationOperator)) {
                // Check only country names/codes for validity, since only those are standardized in ISO 3166.
                if (isValidCountryCode($filterValue)) {
                    $filterValue = getCountryNameFromCode($filterValue);
                } else if (!isValidCountryName($filterValue)) {
                    throw new GeoFilterException("Invalid filter value ($filterValue) (near '$expression')");
                }
            }

            // Case-insensitive comparisons
            if ($originOperator === false) { // ANY origin
                if ($geolocationOperator == 'country' || $geolocationOperator == 'ctry') {
                    $src_result = (strcasecmp($object['src_country'], $filterValue) === 0);
                } else if ($geolocationOperator == 'region' || $geolocationOperator == 'rgn') {
                    $src_result = (strcasecmp($object['src_region'], $filterValue) === 0);
                } else if ($geolocationOperator == 'city' || $geolocationOperator == 'cty') {
                    $src_result = (strcasecmp($object['src_city'], $filterValue) === 0);
                }
                
                if ($geolocationOperator == 'country' || $geolocationOperator == 'ctry') {
                    $dst_result = (strcasecmp($object['dst_country'], $filterValue) === 0);
                } else if ($geolocationOperator == 'region' || $geolocationOperator == 'rgn') {
                    $dst_result = (strcasecmp($object['dst_region'], $filterValue) === 0);
                } else if ($geolocationOperator == 'city' || $geolocationOperator == 'cty') {
                    $dst_result = (strcasecmp($object['dst_city'], $filterValue) === 0);
                }
                
                $result = perform_logic_OR(array($src_result, $dst_result));
            } else {
                if ($originOperator == 'src') {
                    if ($geolocationOperator == 'country' || $geolocationOperator == 'ctry') {
                        $result = (strcasecmp($object['src_country'], $filterValue) === 0);
                    } else if ($geolocationOperator == 'region' || $geolocationOperator == 'rgn') {
                        $result = (strcasecmp($object['src_region'], $filterValue) === 0);
                    } else if ($geolocationOperator == 'city' || $geolocationOperator == 'cty') {
                        $result = (strcasecmp($object['src_city'], $filterValue) === 0);
                    }
                } else { // dst
                    if ($geolocationOperator == 'country' || $geolocationOperator == 'ctry') {
                        $result = (strcasecmp($object['dst_country'], $filterValue) === 0);
                    } else if ($geolocationOperator == 'region' || $geolocationOperator == 'rgn') {
                        $result = (strcasecmp($object['dst_region'], $filterValue) === 0);
                    } else if ($geolocationOperator == 'city' || $geolocationOperator == 'cty') {
                        $result = (strcasecmp($object['dst_city'], $filterValue) === 0);
                    }
                }
            }
            
            $result = ($logicNegationOperator) ? perform_logic_negation($result) : $result;
            // if ($result) error_log("--> Filter result: True");
            // else error_log("--> Filter result: False");
            return $result;
        }
    }
    
    /*
     * TODO
     */
    function check_geo_filter_syntax ($expression) {
        return true;
    }
    
    /*
     * Checks whether the specified expression contains a logic operator (and/or).
     * Parameters:
     *      expression - expression.
     * Return:
     *      First found logic operator (String), in case it was found. Otherwise, 
     *      false.
     */
    function contains_logic_operator ($expression) {
        global $logicOperators;
        foreach ($logicOperators as $operator) {
            if (strpos($expression, " ".$operator) !== false) {
                return $operator;
            }
        }
        unset($operator);
        
        return false;
    }
    
    /*
     * Checks whether the specified expression contains brackets (i.e. '(').
     * Parameters:
     *      expression - expression.
     * Return:
     *      Position of the first found 'opening' bracket (i.e. '(') (int), in case
     *      it was found. Otherwise, false.
     */ 
    function contains_brackets ($expression) {
        $bracketPos = strpos($expression, "(");
        return ($bracketPos === false) ? false : $bracketPos;
    }
    
    /*
     * Checks whether the specified expression contains a logic negation operator 
     *      (i.e. 'not').
     * Parameters:
     *      expression - expression.
     * Return:
     *      True, in case a logic negation operator was found. Otherwise, false.
     */ 
    function contains_logic_negation_operator ($expression) {
        return (strpos($expression, 'not') !== false);
    }
    
    /*
     * Gets the origin operator (i.e. 'src' or 'dst') in case it is present
     * in the specified expression.
     * Parameters:
     *      expression - expression.
     * Return:
     *      First found origin operator, in case it was found. Otherwise, false.
     */ 
    function get_origin_operator ($expression) {
        global $originOperators;
        $found_operator = false;
        
        foreach ($originOperators as $operator) {
            if (strpos($expression, $operator) !== false) {
                $found_operator = $operator;
                break;
            }
        }
        unset($operator);
        
        return $found_operator;
    }
    
    /*
     * Gets the geolocation operator (e.g. 'country' or 'ctry') in case it is present
     * in the specified expression.
     * Parameters:
     *      expression - expression.
     * Return:
     *      First found geolocation operator, in case it was found. Otherwise, false.
     */ 
    function get_geolocation_operator ($expression) {
        global $geolocationOperators;
        $found_operator = false;
        
        foreach ($geolocationOperators as $operator) {
            if (strpos($expression, $operator) !== false) {
                $found_operator = $operator;
                break;
            }
        }
        unset($operator);
        
        return $found_operator;
    }
    
    /*
     * Gets the filter value (e.g. 'NL' or 'The Netherlands') in case it is present
     * in the specified expression.
     * Parameters:
     *      expression - filter expression.
     * Return:
     *      First found filter value.
     */ 
    function get_filter_value ($expression, $logicNegationOperator, $originOperator, $geolocationOperator) {
        if ($geolocationOperator === false) return false;
        
        $operators = ($logicNegationOperator) ? "not " : "";
        
        if ($originOperator === false) {
            $operators .= $geolocationOperator;
        } else {
            $operators .= "$originOperator $geolocationOperator";
        }
        
        $filter_value = substr($expression, strlen($operators) + 1);
        
        return $filter_value;
    }
    
    /*
     * Returns the outer filter expressions as an array.
     * Parameters:
     *      expression - filter expression.
     */ 
    function get_outer_filter_expressions ($expression) {
        if (contains_logic_operator($expression) === false && !contains_brackets($expression) === false) {
            return $expression;
        }
        
        global $logicOperators;

        $outerExpressions = array();
        $expressionDepth = 0;
        $minExpressionDepth = get_minimum_expression_depth($expression);
        $subExpression = "";
        
        for ($i = 0; $i < strlen($expression); $i++) {
            $char = substr($expression, $i, 1);
            
            if ($char === "(") {
                $expressionDepth++;

                // Flush current subexpression (e.g. in case of 'not (... ')
                if ($expressionDepth === ($minExpressionDepth + 1)) {
                    $subExpression = trim($subExpression);
                    
                    if (!empty($subExpression)) {
                        array_push($outerExpressions, $subExpression);
                        $subExpression = "";
                    }
                }
                
                if ($expressionDepth > $minExpressionDepth) {
                    $subExpression .= $char;
                }
            } else if ($char === ")") {
                $expressionDepth--;
                
                // This is essential for expressions such as '(src ctry NL)' (to strip the last bracket properly)
                if ($expressionDepth >= $minExpressionDepth) {
                    $subExpression .= $char;
                }
                
                // Flush current subexpression
                if ($expressionDepth === $minExpressionDepth) {
                    $subExpression = trim($subExpression);
                    
                    if (!empty($subExpression)) {
                        array_push($outerExpressions, $subExpression);
                        $subExpression = "";
                    }
                }           
            } else {
                $subExpression .= $char;
                
                // Check if last keyword was a logic operator
                if ($expressionDepth === $minExpressionDepth) {
                    foreach ($logicOperators as $operator) {
                        if (strpos($subExpression, " ".$operator) !== false) {
                            $subExpression = trim(str_replace($operator, "", $subExpression));
                            
                            // Only add non-empty subexpression. Can be empty in
                            // '(dst ctry CZ) and (src ctry NL)', for instance.
                            if (!empty($subExpression)) {
                                array_push($outerExpressions, $subExpression);
                                $subExpression = "";
                            }
                            
                            array_push($outerExpressions, trim($operator));

                            // Since we check the presence of a logic operator principally
                            // after each char, there can be at most one operator at a time
                            break;
                        }
                    }
                    unset($operator);
                }
            }
        }
        
        // Flush the 'character cache'
        if (!empty($subExpression)) {
            array_push($outerExpressions, trim($subExpression));
            $subExpression = "";
        }

        return $outerExpressions;
    }
    
    /*
     * Determines the minimum expression 'depth' of the provided expression.
     * Examples (hint: check 'and'):
     *      "(dst ctry CZ and (src ctry NL and src ctry DE)) and (src ctry NL or src ctry CZ)" => 0
     *      "((dst ctry CZ and (src ctry NL and src ctry DE)) and (src ctry NL or src ctry CZ))" => 1
     * Parameters:
     *      expression - expression.
     */ 
    function get_minimum_expression_depth ($expression) {
        if (contains_brackets($expression) === false) {
            $minExpressionDepth = 0;
        } else {
            $currentExpressionDepth = 0;
            $minExpressionDepth = -1;

            for ($i = 0; $i < strlen($expression); $i++) {
                $char = substr($expression, $i, 1);

                if ($char === "(") {
                    $currentExpressionDepth++;
                } else if ($char === ")") {
                    $currentExpressionDepth--;
                } else if ($currentExpressionDepth < $minExpressionDepth) {
                    $minExpressionDepth = $currentExpressionDepth;
                }
                
                // Set value after first iteration
                if ($minExpressionDepth === -1) $minExpressionDepth = $currentExpressionDepth;  
            }
        }
        
        return $minExpressionDepth;
    }
    
    /*
     * Performs a logical OR operation on the elements in this array. Returns true
     * if at least one of the elements is true.
     * Parameters:
     *      array - array, of which the elements should be booleans.
     */
    function perform_logic_OR ($array) {
        foreach ($array as $element) {
            if ($element === true) return true;
        }
        unset($element);
        
        return false;
    }
    
    /*
     * Performs a logical AND operation on the elements in this array. Returns true
     * if all elements are true.
     * Parameters:
     *      array - array, of which the elements should be booleans.
     */
    function perform_logic_AND ($array) {
        foreach ($array as $element) {
            if ($element === false) return false;
        }
        unset($element);
        
        return true;
    }   
    
    /*
     * Performs a logical negation operation on the provided element. Returns the
     * negated element.
     * Parameters:
     *      value - (boolean) value that needs to be negated.
     */ 
    function perform_logic_negation ($value) {
        return ($value) ? false : true;
    }
    
    /*
     * Checks whether the specified value is a country geolocation operator (i.e. 'country' or 'ctry')
     * Parameters:
     *      value - value to check.
     * Return:
     *      True (boolean), if the value is a country geolocation operator. Otherwise, false (boolean).
     */ 
    function is_country_geolocation_operator ($value) {
        global $geolocationOperators;
        $country_operators = array();
        
        // Find country operators
        foreach ($geolocationOperators as $operator) {
            if (strcasecmp($operator, "country") === 0 || strcasecmp($operator, "ctry") === 0) {
                array_push($country_operators, $operator);
            }
        }
        unset($operator);

        return (in_arrayi($value, $country_operators));
    }
    
    /*
     * Converts the specified variable into a String value.
     * Parameters:
     *      var - variable to be converted.
     */
    function var_to_string ($var) {
        if ($var === true || $var === 1) {
            $result = "true";
        } else if ($var === false || $var === 0) {
            $result = "false";
        } else if (is_array($var)) {
            $result = "Array(";
            
            for ($i = 0; $i < count($var); $i++) {
                $result .= " [$i] => ".var_to_string($var[$i]);
            }
            
            $result .= " )";
        } else {
            $result = $var;
        }
        
        return $result;
    }
    
    /*
     * Case-insensitive variant of PHP in_array().
     * Parameters:
     *      needle - see PHP in_array().
     *      haystack - see PHP in_array().
     */ 
    function in_arrayi($needle, $haystack) {
        foreach ($haystack as $value) {
            if (strtolower($value) == strtolower($needle)) return true;
        }
        unset($value);
        return false;
    }

    class GeoFilterException extends Exception {
        public function errorMessage() {
            return $this->getMessage();
        }
    }

?>