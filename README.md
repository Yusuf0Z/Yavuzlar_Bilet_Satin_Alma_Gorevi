1. Projeyi İndir
   
git clone https://github.com/Yusuf0Z/bilet-satin-alma.git

2. Proje Dizinine Gir
   
cd bilet-satin-alma

3. Docker Container'ını Başlat
   
docker-compose up -d

4. Kurulumu Kontrol Et
   
docker-compose ps

5. Uygulamayı Aç
# Tarayıcıda otomatik açmak için (Linux/Mac)
open http://localhost:8080

# Veya Windows'ta
start http://localhost:8080

------------------------------------------------------------------------------

🔐 Demo Hesaplar
Sistemi test etmek için aşağıdaki demo hesaplarını kullanabilirsiniz:

👑 Yönetici (Admin) Hesabı
Email: oz@oz.com

Şifre: ozozoz

Erişim: http://localhost:8080/admin_dashboard.php

🏢 Şirket Hesabı
Email: test@test.com

Şifre: testtest

Erişim: http://localhost:8080/company_dashboard.php

👤 Kullanıcı Hesabı
Email: asd@asd.com

Şifre: asdasd

Erişim: http://localhost:8080/dashboard.php
