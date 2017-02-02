<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license.
 */

return [
    'name'        => 'LightspeedRetail',
    'description' => 'Lightspeed Retail API',
    'baseUri'     => 'https://api.merchantos.com/API/',
    'operations'  => [

        /**
         * --------------------------------------------------------------------------------
         * CUSTOMER RELATED METHODS
         *
         * DOC: http://developers.lightspeedhq.com/retail/endpoints/Account-Customer/
         * --------------------------------------------------------------------------------
         */

        'GetCustomers' => [
            'httpMethod'    => 'GET',
            'uri'           => 'Account/{accountID}/Customer.json',
            'responseModel' => 'GenericModel',
            'data'          => [
                'root_key' => 'Customer',
            ],
            'parameters' => [
                'limit' => [
                    'location' => 'query',
                    'required' => false,
                    'type'     => 'integer',
                ],
                'offset' => [
                    'location' => 'query',
                    'required' => false,
                    'type'     => 'integer',
                ],
                'archived' => [
                    'location' => 'query',
                    'required' => false,
                    'type'     => 'boolean',
                ],
                'timeStamp' => [
                    'location' => 'query',
                    'required' => false,
                    'type'     => 'string',
                ],
                'load_relations' => [
                    'location' => 'query',
                    'type'     => 'array',
                    'required' => false,
                    'filters'  => ['json_encode'],
                ],
                'orderby' => [
                    'location' => 'query',
                    'type'     => 'string',
                    'required' => false,
                ],
            ],
            'additionalProperties' => [
                'location' => 'query',
            ],
        ],

        'CreateCustomer' => [
            'httpMethod'    => 'POST',
            'uri'           => 'Account/{accountID}/Customer.json',
            'responseModel' => 'GenericModel',
            'data'          => [
                'root_key' => 'Customer',
            ],
            'parameters' => [
                'accountID' => [
                    'location' => 'uri',
                    'type'     => 'integer',
                    'required' => false,
                ],
                'firstName' => [
                    'location' => 'json',
                    'type'     => 'string',
                    'required' => false,
                ],
                'lastName' => [
                    'location' => 'json',
                    'type'     => 'string',
                    'required' => false,
                ],
                'dob' => [
                    'location' => 'json',
                    'type'     => 'string',
                    'required' => false,
                ],
                'Contact' => [
                    'location' => 'json',
                    'type'     => 'object',
                    'required' => false,
                ],
            ],
        ],

        'UpdateCustomer' => [
            'httpMethod'    => 'PUT',
            'uri'           => 'Account/{accountID}/Customer/{customerID}.json',
            'responseModel' => 'GenericModel',
            'data'          => [
                'root_key' => 'Customer',
            ],
            'parameters' => [
                'accountID' => [
                    'location' => 'uri',
                    'type'     => 'integer',
                    'required' => false,
                ],
                'customerID' => [
                    'location' => 'uri',
                    'type'     => 'integer',
                    'required' => true,
                ],
                'firstName' => [
                    'location' => 'json',
                    'type'     => 'string',
                    'required' => false,
                ],
                'lastName' => [
                    'location' => 'json',
                    'type'     => 'string',
                    'required' => false,
                ],
                'dob' => [
                    'location' => 'json',
                    'type'     => 'string',
                    'required' => false,
                ],
                'Contact' => [
                    'location' => 'json',
                    'type'     => 'object',
                    'required' => false,
                ],
            ],
        ],

        /**
         * --------------------------------------------------------------------------------
         * ITEM RELATED METHODS
         *
         * DOC: http://developers.lightspeedhq.com/retail/endpoints/Account-Item/
         * --------------------------------------------------------------------------------
         */

        'GetItems' => [
            'httpMethod'    => 'GET',
            'uri'           => 'Account/{accountID}/Item.json',
            'responseModel' => 'GenericModel',
            'data'          => [
                'root_key' => 'Item',
            ],
            'parameters' => [
                'limit' => [
                    'location' => 'query',
                    'required' => false,
                    'type'     => 'integer',
                ],
                'offset' => [
                    'location' => 'query',
                    'required' => false,
                    'type'     => 'integer',
                ],
                'archived' => [
                    'location' => 'query',
                    'required' => false,
                    'type'     => 'boolean',
                ],
                'timeStamp' => [
                    'location' => 'query',
                    'required' => false,
                    'type'     => 'string',
                ],
                'load_relations' => [
                    'location' => 'query',
                    'type'     => 'array',
                    'required' => false,
                    'filters'  => ['json_encode'],
                ],
                'orderby' => [
                    'location' => 'query',
                    'type'     => 'string',
                    'required' => false,
                ],
            ],
            'additionalProperties' => [
                'location' => 'query',
            ],
        ],

        'CreateItem' => [
            'httpMethod'    => 'POST',
            'uri'           => 'Account/{accountID}/Item.json',
            'responseModel' => 'GenericModel',
            'data'          => [
                'root_key' => 'Item',
            ],
            'parameters' => [
                'description' => [
                    'location' => 'json',
                    'type'     => 'string',
                    'required' => false,
                ],
                'customSku' => [
                    'location' => 'json',
                    'type'     => 'string',
                    'required' => false,
                ],
                'Prices' => [
                    'location' => 'json',
                    'type'     => 'object',
                    'required' => false,
                ],
                'Images' => [
                    'location' => 'json',
                    'type'     => 'object',
                    'required' => false,
                ],
            ],
        ],

        'UpdateItem' => [
            'httpMethod'    => 'PUT',
            'uri'           => 'Account/{accountID}/Item/{itemID}.json',
            'responseModel' => 'GenericModel',
            'data'          => [
                'root_key' => 'Item',
            ],
            'parameters' => [
                'description' => [
                    'location' => 'json',
                    'type'     => 'string',
                    'required' => false,
                ],
                'customSku' => [
                    'location' => 'json',
                    'type'     => 'string',
                    'required' => false,
                ],
                'Prices' => [
                    'location' => 'json',
                    'type'     => 'object',
                    'required' => false,
                ],
                'Images' => [
                    'location' => 'json',
                    'type'     => 'object',
                    'required' => false,
                ],
            ],
        ],

        /**
         * --------------------------------------------------------------------------------
         * SALE RELATED METHODS
         *
         * DOC: http://developers.lightspeedhq.com/retail/endpoints/Account-Sale/
         * --------------------------------------------------------------------------------
         */

        'GetSales' => [
            'httpMethod'    => 'GET',
            'uri'           => 'Account/{accountID}/Sale.json',
            'responseModel' => 'GenericModel',
            'data'          => [
                'root_key' => 'Item',
            ],
            'parameters' => [
                'limit' => [
                    'location' => 'query',
                    'required' => false,
                    'type'     => 'integer',
                ],
                'offset' => [
                    'location' => 'query',
                    'required' => false,
                    'type'     => 'integer',
                ],
                'archived' => [
                    'location' => 'query',
                    'required' => false,
                    'type'     => 'boolean',
                ],
                'timeStamp' => [
                    'location' => 'query',
                    'required' => false,
                    'type'     => 'string',
                ],
                'load_relations' => [
                    'location' => 'query',
                    'type'     => 'array',
                    'required' => false,
                    'filters'  => ['json_encode'],
                ],
                'orderby' => [
                    'location' => 'query',
                    'type'     => 'string',
                    'required' => false,
                ],
            ],
            'additionalProperties' => [
                'location' => 'query',
            ],
        ],
    ],

    'models' => [
        'GenericModel' => [
            'type'                 => 'object',
            'additionalProperties' => [
                'location' => 'json',
            ],
        ],
    ],
];
