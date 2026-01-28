    <?php
    echo "<h1>Server Upload Limits</h1>";
    
    $upload_max_filesize = ini_get('upload_max_filesize');
    echo "<p>Maximum size of a single uploaded file (<strong>upload_max_filesize</strong>): <strong>{$upload_max_filesize}</strong></p>";

    $post_max_size = ini_get('post_max_size');
    echo "<p>Total size of all POST data, including files (<strong>post_max_size</strong>): <strong>{$post_max_size}</strong></p>";

    echo "<hr><p>Ikumpara mo ang mga values na ito sa laki ng iyong CSV file. Ang 'post_max_size' ay dapat kasing laki o mas malaki sa 'upload_max_filesize'.</p>";
    ?>