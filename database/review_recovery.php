<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
echo "Use the authenticated administrator dashboard to review claims and download evidence.\n";
