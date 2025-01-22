<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Database\Capsule;

// Debug function
function vps_backup_debug($message, $data = null) {
    $debug_file = __DIR__ . '/debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[{$timestamp}] {$message}";
    if ($data !== null) {
        $log_message .= "\n" . print_r($data, true);
    }
    $log_message .= "\n";
    file_put_contents($debug_file, $log_message, FILE_APPEND);
    logActivity("VPS Backup: " . $message);
}

function vps_backup_calculator_config() {
    return [
        'name' => 'VPS Backup Calculator',
        'description' => 'Adds backup option to VPS plans with complex price calculation',
        'version' => '1.0',
        'author' => 'SuperHosting',
        'fields' => [
            'vps_product_groups' => [
                'FriendlyName' => 'VPS Product Groups',
                'Type' => 'dropdown',
                'Options' => function () {
                    $groups = Capsule::table('tblproductgroups')->get();
                    $options = [];
                    foreach ($groups as $group) {
                        $options[$group->id] = $group->name;
                    }
                    return $options;
                },
                'Description' => 'Select the product group that contains VPS products.',
            ],
        ]
    ];
}


function vps_backup_calculator_calc_backup_price($params) {
    try {
        vps_backup_debug("Calculating backup price for params:", $params);

        $productId = $params['pid'];
        $currencyId = $params['currencyid'];
        $billingCycle = $params['billingcycle'];
        
        // Find the base product price based on the billing cycle from the tblpricing table
        $basePrice = Capsule::table('tblpricing')
            ->where('type', 'product')
            ->where('relid', $productId)
            ->where('currency', $currencyId)
            ->value($billingCycle);

            if (!$basePrice) {
                vps_backup_debug("No base price found for product ID: " . $productId . " and currency: " . $currencyId . " for billing cycle: " . $billingCycle);
                return 0;
            }
        
        vps_backup_debug("Base price: " . $basePrice);

        // Calculate backup price (20% of base price + 31% of backup price)
        $backupPrice = $basePrice * 0.20;
        $finalPrice = $backupPrice + ($backupPrice * 0.31);
        
        vps_backup_debug("Calculated backup price: " . $finalPrice);
        
        return $finalPrice;

    } catch (\Exception $e) {
        vps_backup_debug("Error calculating backup price: " . $e->getMessage());
        return 0;
    }
}

function vps_backup_calculator_activate()
{
     try {
        vps_backup_debug("Activating module");

        // Get module settings
        $settings = vps_backup_get_settings();

        // Get product groups from settings
        $vpsGroups = !empty($settings['vps_product_groups']) ? [$settings['vps_product_groups']] : [];

        vps_backup_debug("VPS Groups to process:", $vpsGroups);

        // Create or get config group
        $configGroup = Capsule::table('tblproductconfiggroups')
            ->where('name', 'VPS Backup Options')
            ->first();

        if (!$configGroup) {
            vps_backup_debug("Creating new config group");
            $configGroupId = Capsule::table('tblproductconfiggroups')->insertGetId([
                'name' => 'VPS Backup Options',
                'description' => 'Backup options for VPS plans'
            ]);
        } else {
            $configGroupId = $configGroup->id;
            vps_backup_debug("Using existing config group with ID: " . $configGroupId);
        }

        // Create or get backup option
        $option = Capsule::table('tblproductconfigoptions')
            ->where('gid', $configGroupId)
            ->where('optionname', 'Backup Service')
            ->first();

        if (!$option) {
            vps_backup_debug("Creating missing backup option");
            $optionId = Capsule::table('tblproductconfigoptions')->insertGetId([
                'gid' => $configGroupId,
                'optionname' => 'Backup Service',
                'optiontype' => 3,
                'qtyminimum' => 0,
                'qtymaximum' => 0,
                'order' => 0,
                'hidden' => 0
            ]);
            
             // Add Yes/No sub-options with pricing
            $subOptions = [
                [
                    'configid' => $optionId,
                    'optionname' => 'No',
                    'sortorder' => 0,
                    'hidden' => 0
                ],
                [
                    'configid' => $optionId,
                    'optionname' => 'Yes',
                    'sortorder' => 1,
                    'hidden' => 0
                ]
            ];
            
           foreach ($subOptions as $subOption) {
                $subOptionId = Capsule::table('tblproductconfigoptionssub')->insertGetId($subOption);
                vps_backup_debug("Created sub-option: " . $subOption['optionname'] . " with ID: " . $subOptionId);
            }
        } else {
            $optionId = $option->id;
            vps_backup_debug("Using existing backup option with ID: " . $optionId);
        }
      
      

        // Link config group to VPS products
       foreach ($vpsGroups as $groupId) {
            if (empty($groupId)) continue;

            $products = Capsule::table('tblproducts')
                ->where('gid', $groupId)
                ->get();
            
             foreach ($products as $product) {
                 vps_backup_debug("Processing product: " . $product->name . " (ID: " . $product->id . ")");
                $existingLink = Capsule::table('tblproductconfiglinks')
                    ->where('pid', $product->id)
                    ->where('gid', $configGroupId)
                    ->first();
                if (!$existingLink) {
                    Capsule::table('tblproductconfiglinks')->insert([
                        'pid' => $product->id,
                        'gid' => $configGroupId
                    ]);
                    vps_backup_debug("Linked product ID " . $product->id . " with config group");
                } else {
                    vps_backup_debug("Product ID " . $product->id . " already linked to config group");
                }
             }
         }


        return ['status' => 'success', 'description' => 'VPS Backup Calculator module activated successfully.'];

    } catch (\Exception $e) {
        vps_backup_debug("Activation error: " . $e->getMessage());
        return ['status' => 'error', 'description' => 'Error activating module: ' . $e->getMessage()];
    }
}
add_hook('ShoppingCartCheckoutCompletePage', 1, function($vars) {
      try {
        vps_backup_debug("Hook triggered: ShoppingCartCheckoutCompletePage");
    
           // Get module settings
        $moduleSettings = vps_backup_get_settings();
        $vpsGroups = !empty($moduleSettings['vps_product_groups']) ? [$moduleSettings['vps_product_groups']] : [];
        vps_backup_debug("VPS Groups:", $vpsGroups);

        if (empty($vars['products'])) {
            vps_backup_debug("No products in cart");
            return $vars;
        }
     
         foreach ($vars['products'] as $product) {
            vps_backup_debug("Processing product:", $product);
            
            $productInfo = Capsule::table('tblproducts')
                ->where('id', $product['pid'])
                ->first();
                
            if (!$productInfo) {
                vps_backup_debug("Product not found: " . $product['pid']);
                continue;
            }
             vps_backup_debug("Product Info:", (array)$productInfo);
               if (in_array($productInfo->gid, $vpsGroups)) {
                    vps_backup_debug("VPS product found. ID: " . $product['pid']);
                     // Get the backup option value
                    $backupOption = false;
                    if (isset($product['configoptions'])) {
                        foreach ($product['configoptions'] as $option) {
                            if (strtolower($option['name']) === 'backup service' && $option['value'] === 'Yes') {
                                $backupOption = true;
                                break;
                            }
                        }
                    }
                     if ($backupOption) {
                        vps_backup_debug("Backup option selected for product ID: " . $product['pid']);
                        // Calculate the backup price
                        $params = [
                            'pid' => $product['pid'],
                            'currencyid' => $vars['currency']['id'],
                            'billingcycle' => $product['billingcycle']
                        ];
                        $backupPrice = vps_backup_calculator_calc_backup_price($params);
                         
                        vps_backup_debug("Final backup price to add : ". $backupPrice );
                        
                        // Add the backup price to the invoice items
                          Capsule::table('tblinvoiceitems')->insert([
                                'invoiceid' => $vars['invoiceid'],
                                'userid' => $vars['userid'],
                                'type' => 'Backup Service for '.$productInfo->name,
                                'relid' => $product['id'],
                                'description' => 'Backup Service for '.$productInfo->name,
                                'amount' => $backupPrice,
                                'taxed' => 1, // Assuming backup is taxable
                                 'duedate' => date('Y-m-d'),
                                  'paymentmethod' => $vars['paymentmethod'],
                            ]);
                            
                    
                    }
                }
         }
          return $vars;
      } catch (\Exception $e) {
        vps_backup_debug("Error in hook: " . $e->getMessage());
        return $vars;
    }
});

function vps_backup_get_settings() {
    try {
        $settings = [];
        $results = Capsule::table('tbladdonmodules')
            ->where('module', 'vps_backup_calculator')
            ->get();

        foreach ($results as $row) {
            $settings[$row->setting] = $row->value;
        }
        vps_backup_debug("Module settings:", $settings);
        return $settings;
    } catch (\Exception $e) {
        vps_backup_debug("Error getting settings: " . $e->getMessage());
        return [];
    }
}