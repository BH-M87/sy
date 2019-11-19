<?php
$basePath = dirname(__DIR__,2);
$paramsConfig['test'] = [
    'fuyang' => [
        'host_name' => 'https://test-fy.elive99.com',
        'small_app' => [
            //邻易联小程序
            'fczl_app_id' => '2019071165794353',
            'fczl_alipay_public_key_file' => $basePath."/common/rsa_files/fczl/alipay_public.txt",
            'fczl_rsa_private_key_file' => $basePath."/common/rsa_files/fczl/rsa_private.txt",
            'fczl_aes_secret' => "EBG7v29Z3B4+DYuGk1a0ww==",

            //门禁小程序
            'edoor_app_id' => '2019031663543853',
            'edoor_alipay_public_key_file' => $basePath."/common/rsa_files/edoor/alipay_public.txt",
            'edoor_rsa_private_key_file' => $basePath."/common/rsa_files/edoor/rsa_private.txt",
            'edoor_aes_secret' => "Glu/dZr2xWDPCAiFAcZkCw==",

            //党建小程序
            'djyl_app_id' => '2019082866552086',
            'djyl_alipay_public_key_file' => $basePath."/common/rsa_files/djyl/alipay_public.txt",
            'djyl_rsa_private_key_file' => $basePath."/common/rsa_files/djyl/rsa_private.txt",
            'djyl_aes_secret' => 'ee1ysBQwEIBmbCO7++GEvw==',
        ],
        'iotNewUrl' => 'http://101.37.135.54:8844',
        'java_domain' => ' http://47.103.151.121:8889/',
        'oss_bucket' => 'sqwn-fy',
        //钉钉应用配置
        'dd_app' => [
            'appKey' => 'dingrxn2bgp2ngekcak5',
            'appSecret' => 'S_ovO-YELdDYpuZ79hcn2NWCZLyryzgCZRIozq9xhfPqagnHHgbIIMrNgOxiZOOT',
            'agent_id' => '290532532'
        ],
    ],
    'hefei' => [
        'host_name' => 'https://test-hf.elive99.com',
        'small_app' => [
            //邻易联小程序
            'fczl_app_id' => '2019101068202998',
            'fczl_alipay_public_key_file' => $basePath."/common/rsa_files/hefei-fczl/alipay_public.txt",
            'fczl_rsa_private_key_file' => $basePath."/common/rsa_files/hefei-fczl/rsa_private.txt",
            'fczl_aes_secret' => "E90nXpF6U+G0Ijtcsl9ucA==",

            //门禁小程序
            'edoor_app_id' => '2019101068237958',
            'edoor_alipay_public_key_file' => $basePath."/common/rsa_files/hefei-edoor/alipay_public.txt",
            'edoor_rsa_private_key_file' => $basePath."/common/rsa_files/hefei-edoor/rsa_private.txt",
            'edoor_aes_secret' => "e+cWTywFOvsNOwf9P2DZBg==",

            //党建小程序
            'djyl_app_id' => '2019101068202999',
            'djyl_alipay_public_key_file' => $basePath."/common/rsa_files/hefei-djyl/alipay_public.txt",
            'djyl_rsa_private_key_file' => $basePath."/common/rsa_files/hefei-djyl/rsa_private.txt",
            'djyl_aes_secret' => 'wrQRqNPKjii/FRMbCUR3kA==',
        ],
        'iotNewUrl' => 'http://101.37.135.54:8844',
        'java_domain' =>  'http://47.103.151.121:8700/',
        'oss_bucket' => 'sqwn-ss',
        //钉钉应用配置
        'dd_app' => [
            'appKey' => 'dingnwwsei87rubgtdty',
            'appSecret' => 'pn5BSZ7jJlpbgDGTbfQClIs3Y6SBJm6L0u57fDjZRrgp9U_CPOb9l5pIjcoXLPAx',
            'agent_id' => '301180256'
        ],
    ],
    'wuchang' => [
        //暂无
        'host_name' => 'https://test-wuchang.elive99.com',
        //暂无
        'small_app' => [

        ],
        'iotNewUrl' => 'http://101.37.135.54:8844',
        'java_domain' => 'http://47.103.151.121:8900/',
        'oss_bucket' => 'sqwn-wc',
        //钉钉应用配置
        'dd_app' => [
        ],
    ],
    'saas' => [
        'host_name' => 'https://test-saas.elive99.com',
        'small_app' => [
            //合并小程序
            'fczl_app_id' => '2019103168778211',
            'fczl_alipay_public_key_file' => $basePath."/common/rsa_files/saas/alipay_public.txt",
            'fczl_rsa_private_key_file' => $basePath."/common/rsa_files/saas/rsa_private.txt",
            'fczl_aes_secret' => 'Kmlrm+BP2EL5OpDkmfN/GA==',
        ],
        'iotNewUrl' => 'http://101.37.135.54:8844',
        'java_domain' => 'http://47.103.151.121:8819/',
        'oss_bucket' => 'sqwn-saas',
        //钉钉应用配置,待定
        'dd_app' => [
            'appKey' => 'dingnwwsei87rubgtdty',
            'appSecret' => 'pn5BSZ7jJlpbgDGTbfQClIs3Y6SBJm6L0u57fDjZRrgp9U_CPOb9l5pIjcoXLPAx',
            'agent_id' => '301180256'
        ],
    ],
    'yanshi' => [
        //暂无
        'host_name' => 'https://test-saas.elive99.com',
        'small_app' => [

        ],
        'iotNewUrl' => 'http://101.37.135.54:8844',
        'java_domain' => 'http://47.103.151.121:8800/',
        'oss_bucket' => 'sqwn-yanshi',
        //钉钉应用配置,待定
        'dd_app' => [
        ],
    ]

];

$paramsConfig['prod'] = [
    'fuyang' => [
        'host_name' => 'https://sqwn-fy-web.elive99.com/',
        'small_app' => [
            //邻易联小程序
            'fczl_app_id' => '2019071165794353',
            'fczl_alipay_public_key_file' => $basePath."/common/rsa_files/fczl/alipay_public.txt",
            'fczl_rsa_private_key_file' => $basePath."/common/rsa_files/fczl/rsa_private.txt",
            'fczl_aes_secret' => "EBG7v29Z3B4+DYuGk1a0ww==",

            //门禁小程序
            'edoor_app_id' => '2019031663543853',
            'edoor_alipay_public_key_file' => $basePath."/common/rsa_files/edoor/alipay_public.txt",
            'edoor_rsa_private_key_file' => $basePath."/common/rsa_files/edoor/rsa_private.txt",
            'edoor_aes_secret' => "Glu/dZr2xWDPCAiFAcZkCw==",

            //党建小程序
            'djyl_app_id' => '2019082866552086',
            'djyl_alipay_public_key_file' => $basePath."/common/rsa_files/djyl/alipay_public.txt",
            'djyl_rsa_private_key_file' => $basePath."/common/rsa_files/djyl/rsa_private.txt",
            'djyl_aes_secret' => 'ee1ysBQwEIBmbCO7++GEvw==',
        ],
        'iotNewUrl' => 'https://gateway-api.zje.com',
        'java_domain' => 'http://192.168.9.159:8888/',
        'oss_bucket' => 'sqwn-fy',
        //钉钉应用配置
        'dd_app' => [
            'appKey' => 'dingvxqretqs7uduiovc',
            'appSecret' => '06YC5GujdrjBqydJuEt4P6SieVl9YdmZZwVXJ0XSOQPJ1seJ1mSEC1HIpHGJqhN2',
            'agent_id' => '281128929'
        ],


    ],
    'hefei' => [
        'host_name' => 'https://sqwn-ss-web.elive99.com',
        'small_app' => [
            //邻易联小程序
            'fczl_app_id' => '2019101068202998',
            'fczl_alipay_public_key_file' => $basePath."/common/rsa_files/hefei-fczl/alipay_public.txt",
            'fczl_rsa_private_key_file' => $basePath."/common/rsa_files/hefei-fczl/rsa_private.txt",
            'fczl_aes_secret' => "E90nXpF6U+G0Ijtcsl9ucA==",

            //门禁小程序
            'edoor_app_id' => '2019101068237958',
            'edoor_alipay_public_key_file' => $basePath."/common/rsa_files/hefei-edoor/alipay_public.txt",
            'edoor_rsa_private_key_file' => $basePath."/common/rsa_files/hefei-edoor/rsa_private.txt",
            'edoor_aes_secret' => "e+cWTywFOvsNOwf9P2DZBg==",

            //党建小程序
            'djyl_app_id' => '2019101068202999',
            'djyl_alipay_public_key_file' => $basePath."/common/rsa_files/hefei-djyl/alipay_public.txt",
            'djyl_rsa_private_key_file' => $basePath."/common/rsa_files/hefei-djyl/rsa_private.txt",
            'djyl_aes_secret' => 'wrQRqNPKjii/FRMbCUR3kA==',
        ],
        'iotNewUrl' => 'https://gateway-api.zje.com',
        'java_domain' => 'http://192.168.9.159:8898/',
        'oss_bucket' => 'sqwn-ss',
        //钉钉应用配置
        'dd_app' => [
            'appKey' => 'dingnwwsei87rubgtdty',
            'appSecret' => 'pn5BSZ7jJlpbgDGTbfQClIs3Y6SBJm6L0u57fDjZRrgp9U_CPOb9l5pIjcoXLPAx',
            'agent_id' => '301180256'
        ],
    ],
    'wuchang' => [
        'host_name' => 'https://sqwn-5c-web.elive99.com/',
        //暂无
        'small_app' => [

        ],
        'iotNewUrl' => 'http://101.37.135.54:8844',
        'java_domain' => '',
        'oss_bucket' => 'sqwn-wc',
        //钉钉应用配置
        'dd_app' => [
        ],
    ],
    'saas' => [
        'host_name' => 'https://sqwn-saas.elive99.com',
        'small_app' => [
            //合并小程序
            'fczl_app_id' => '2019103168778211',
            'fczl_alipay_public_key_file' => $basePath."/common/rsa_files/saas/alipay_public.txt",
            'fczl_rsa_private_key_file' => $basePath."/common/rsa_files/saas/rsa_private.txt",
            'fczl_aes_secret' => 'Kmlrm+BP2EL5OpDkmfN/GA==',
        ],
        'iotNewUrl' => 'https://gateway-api.zje.com',
        //待定
        'java_domain' => 'http://192.168.4.129:8888/',
        'oss_bucket' => 'sqwn-saas',
        //钉钉应用配置，待定
        'dd_app' => [
            'appKey' => 'dingnwwsei87rubgtdty',
            'appSecret' => 'pn5BSZ7jJlpbgDGTbfQClIs3Y6SBJm6L0u57fDjZRrgp9U_CPOb9l5pIjcoXLPAx',
            'agent_id' => '301180256'
        ],
    ]
];

return $paramsConfig;