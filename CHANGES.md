### Syrian 2.0: 
* 01. controller#method only return data to output                                      --done
* 02. add json_view(STATUS_OK, 'Ok'), json_define_view(STATUS_OK, 'Ok');                --done
* 03. add html_view('stream/view.html', array(), true);                                 --done
* 04. optimize the helper() to helper('Session#User', array());                         --done
* 05. add application layer function                                                    --done
* 06. new uri parse and return the parsed info object                                   --done
* 07. add function controller('api.stream.view', $args);                                --done
* 08. add session quick manager                                                         --done
* 09. add namespace support for controller                                              --ignore
* 10. add #service('path', args, $executor=local executor, asyn, priority) function     --done
* 11. optimize the Local service executor(no arguments serialize and unserialize)       --done
* 12. Uri with self-define arguments support
