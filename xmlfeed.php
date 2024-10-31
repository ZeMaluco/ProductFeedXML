<?php
class ControllerExtensionFeedXmlFeedTeste extends Controller {
    public function plainText($description) {

        $description = strip_tags(html_entity_decode($description));
        $description = str_replace('&nbsp;', ' ', $description);
        $description = str_replace('"', '&#34;', $description);
        $description = str_replace("'", '&#39;', $description);
        $description = str_replace('<', '&lt;', $description);
        $description = str_replace('>', '&gt;', $description);
        $description = str_replace("\n", ' ', $description);
        $description = str_replace("\r", ' ', $description);
        $description = preg_replace('/&#?[a-z0-9]+;/i', ' ', $description);
        $description = preg_replace('/\s{2,}/i', ' ', $description);
        return substr($description, 0, 5000);
    }
    
    public function index() {
        if ($this->config->get('xmlfeedteste_status')) {
            $output  = '<?xml version="1.0" encoding="UTF-8"?>';
            $output .= '<rss xmlns:g="http://base.google.com/ns/1.0" version="2.0">';
            $output .= '<channel>';
            $this->load->model('catalog/product');
            $this->load->model('catalog/category');
            $this->load->model('tool/image');

            $products = $this->model_catalog_product->getProducts();
            foreach ($products as $product) {
                if ($product['image']) {

                    $output .= '<item>';
                    $output .= '<g:id>' . $product['product_id'] . '</g:id>';
                    $output .= '<g:title>' . $product['name'] . '</g:title>';

                    $description = $this->cleanText($this->plainText($product['description']));
                    $description = $this->utf8_for_xml($description);

                    $output .= '<g:description>' . $description .'</g:description>';
                    $output .= '<g:availability>' . ($product['quantity'] ? 'in stock' : 'out of stock') . '</g:availability>';
                    $output .= '<g:condition>new</g:condition>';


                    $currencies = array(
                        'USD',
                        'EUR',
                        'GBP',
                        'ZAR',
                        'PHP'
                    );

                    if (in_array($this->session->data['currency'], $currencies)) {
                        $currency_code = $this->session->data['currency'];
                        $currency_value = $this->currency->getValue($this->session->data['currency']);
                    } else {
                        $currency_code = 'EUR';
                        $currency_value = $this->currency->getValue('EUR');
                    }


                    $output .= "<g:price>" . $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id']), $currency_code, $currency_value, false) . ' EUR'. '</g:price>';
                    $output .= '<g:link>' . $this->url->link('product/product', 'product_id=' . $product['product_id']) . '</g:link>';
                    if ($product['image']) {
                        $output .= '  <g:image_link>' . $this->model_tool_image->resize($product['image'], 500, 500) . '</g:image_link>';
                    } else {
                        $output .= '  <g:image_link></g:image_link>';
                    }
                    $output .= '<g:brand>' . $product['manufacturer'] . '</g:brand>';
                    if ($product['mpn']) {
                        $output .= '<g:mpn>' . $product['mpn'] . '</g:mpn>';
                    }

                    $output .= '  <g:google_product_category>491</g:google_product_category>';
                    $categories = $this->model_catalog_product->getCategories($product['product_id']);
                    unset($categoryy1);
                    unset($categoryy2);
                    unset($categoryy3);
                    foreach ($categories as $category) {
                        $path = $this->getPath($category['category_id']);
                        if ($path) {
                            $string = '';
                            $p = explode('_', $path,2);
                            $cat1 = $p[0];
                            foreach ((array)$cat1 as $path_id) {
                                $category_info = $this->model_catalog_category->getCategory($path_id);
                                if ($category_info) {
                                    if (!$string) {
                                       $categoryy1[] =$category_info['name'];
                                       
                                    }
                                }                           
                                if ( ! isset($p[1])) {
                                    $p[1] = ' ';
                                }
                                $cat2 = explode('_', $p[1]);

                                foreach ((array)$cat2[0] as $path_id) {
                                    $category_info = $this->model_catalog_category->getCategory($path_id);
                                    if ($category_info) {
                                        if (!$string) {
                                              $categoryy2[] =$category_info['name'];
                                            
                                        }
                                    }
                                }
                                if ( ! isset($cat2[1])) {
                                    $cat2[1] = ' ';
                                }
                                foreach ((array)$cat2[1] as $path_id) {
                                    $category_info = $this->model_catalog_category->getCategory($path_id);
                                    if ($category_info) {
                                        if (!$string) {
                                              $categoryy3[] =$category_info['name'];
                                            
                                        }
                                    }
                                }
                            }
                        }   
                    }
                    $output .= '<g:custom_Label_0>' . implode(", ", $categoryy1) . '</g:custom_Label_0>';
                    $output .= '<g:custom_Label_1>' . implode(", ", $categoryy2) . '</g:custom_Label_1>';
                    $output .= '<g:custom_Label_2>' . implode(", ", $categoryy3) . '</g:custom_Label_2>';

                    if ($product['ean']) {
                        $output .= '<g:gtin>' . $product['ean'] . '</g:gtin>';
                    }else{
                        $output .= '<g:identifier_exists>no</g:identifier_exists>';
                    }
                    $output .= '</item>';
                }
            }
            $output .= '</channel>';
            $output .= '</rss>';
            $this->response->addHeader('Content-Type: application/xml');
            $this->response->setOutput($output);
        }
    }

    protected function getPath($parent_id, $current_path = '') {
        $category_info = $this->model_catalog_category->getCategory($parent_id);
        if ($category_info) {
            $new_path = $category_info['category_id'] . ($current_path ? '_' . $current_path : '');
            $path = $this->getPath($category_info['parent_id'], $new_path);
            return $path ?: $new_path;
        } 
        return ''; // Added return for null category
    }
    
    private function cleanText($str = '', $BdField = '') {
        // Encode or decode based on UTF-8 detection
        if (function_exists('mb_detect_encoding') && mb_detect_encoding($str, 'UTF-8', true) !== 'UTF-8') {
            $str = utf8_encode($str);
        }
    
        $str = stripslashes($str);
        $str = htmlspecialchars($str, ENT_QUOTES, 'UTF-8'); // Sanitizes after stripping slashes
        return $str;
    }
    
    private function __utf8_decode($str = '') {
        if ($str !== '' && mb_detect_encoding($str, 'UTF-8', true)) {
            return utf8_decode($str);
        }
        return $str;
    }
    
    private function __utf8_encode($str = '') {
        if ($str !== '' && mb_detect_encoding($str, 'UTF-8', true) === false) {
            return utf8_encode($str);
        }
        return $str;
    }
    
    // UTF-8 for XML-safe characters only
    function utf8_for_xml($string) {
        return preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', ' ', $string);
    }
}    
