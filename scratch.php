<?php
require_once 'db.php';

try {
    $db = getDB();
    echo "Connected to Supabase PostgreSQL successfully!\n";

    // 1. Create table
    $sqlCreateTable = "
    CREATE TABLE IF NOT EXISTS product_reviews (
      id SERIAL PRIMARY KEY,
      product_id INT NOT NULL REFERENCES products(id) ON DELETE CASCADE,
      user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
      rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
      comment TEXT NOT NULL,
      created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
    );
    ";
    
    $db->exec($sqlCreateTable);
    echo "Table 'product_reviews' verified/created successfully!\n";

    // 2. Check if table is empty
    $stmt = $db->query("SELECT COUNT(*) FROM product_reviews");
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        echo "Seeding default reviews data...\n";
        $sqlSeed = "
        INSERT INTO product_reviews (product_id, user_id, rating, comment, created_at) VALUES
        (1, 3, 5, 'Bukunya sangat mulus! Tidak ada coretan yang mengganggu, halaman lengkap. Penjual juga sangat ramah saat COD di perpus.', '2026-06-12 10:00:00'),
        (1, 4, 4, 'Kondisi buku sesuai deskripsi. Cukup membantu untuk kuliah kalkulus semester ini.', '2026-06-10 14:30:00'),
        (2, 2, 5, 'Gila keren banget iPad-nya! Mulus banget seperti baru, dapet stylus juga. Terbantu sekali buat tugas desain grafis.', '2026-06-14 09:15:00'),
        (3, 3, 5, 'Jaketnya masih wangi dan bersih. Ukurannya pas banget sesuai deskripsi penjual. Makasih kak!', '2026-06-11 16:45:00'),
        (4, 4, 5, 'Kopinya enak banget, segar dan gak terlalu asam. Cocok buat nemenin begadang ngerjain tugas.', '2026-06-16 20:00:00'),
        (4, 3, 4, 'Rasa kopinya mantap! Pengirimannya cepat dan masih dingin pas sampai.', '2026-06-15 11:10:00'),
        (5, 2, 5, 'Hasil terjemahannya sangat rapi dan grammar-nya akurat. Pengerjaan cepat, sangat recommended buat tugas akhir!', '2026-06-08 13:20:00');
        ";
        $db->exec($sqlSeed);
        echo "Seeding completed successfully!\n";
    } else {
        echo "Table already contains $count reviews. Skipping seed.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
