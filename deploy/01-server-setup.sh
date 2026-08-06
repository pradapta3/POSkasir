#!/usr/bin/env bash
#
# POS Kasir — Langkah 1: setup awal server
# Jalankan sebagai root, SEKALI SAJA, di VPS Ubuntu 24.04 yang baru/bersih.
#
# Domain : mbayar.my.id
# IP VPS : 202.155.14.70
#
# Baca dulu isinya sebelum dijalankan. Cara pakai:
#   1. Copy-paste isi file ini ke VPS (nano 01-server-setup.sh, paste, simpan)
#   2. bash 01-server-setup.sh
#
set -euo pipefail

VPS_IP="202.155.14.70"

echo "==> Update sistem"
apt update && apt upgrade -y

echo "==> Buat user 'deploy' (kalau belum ada)"
if ! id -u deploy >/dev/null 2>&1; then
    adduser --disabled-password --gecos "" deploy
    usermod -aG sudo deploy
fi

echo "==> Salin authorized_keys root ke user deploy (kalau ada)"
mkdir -p /home/deploy/.ssh
if [ -f /root/.ssh/authorized_keys ]; then
    cp /root/.ssh/authorized_keys /home/deploy/.ssh/authorized_keys
fi
chown -R deploy:deploy /home/deploy/.ssh
chmod 700 /home/deploy/.ssh
chmod 600 /home/deploy/.ssh/authorized_keys 2>/dev/null || true

echo "==> Firewall (ufw): hanya izinkan SSH, HTTP, HTTPS"
apt install -y ufw
ufw allow OpenSSH
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable

echo "==> fail2ban (blokir IP yang brute-force SSH)"
apt install -y fail2ban
systemctl enable --now fail2ban

cat <<EOF

==================================================================
 Selesai. JANGAN LEWATI langkah berikut sebelum lanjut:

 1. Dari KOMPUTER LOKALMU (bukan di VPS ini), pastikan bisa login
    sebagai 'deploy' pakai SSH key:

      ssh deploy@${VPS_IP}

    Kalau /root/.ssh/authorized_keys tadi kosong, atau kamu login ke
    VPS ini pakai password (bukan key), jalankan dulu dari komputer
    lokalmu:

      ssh-copy-id deploy@${VPS_IP}

 2. Setelah YAKIN bisa login sebagai 'deploy' lewat SSH key, baru
    matikan login root & password auth SSH — edit
    /etc/ssh/sshd_config di VPS ini, set:

      PermitRootLogin no
      PasswordAuthentication no

    lalu: systemctl restart ssh

 3. Logout, login lagi sebagai 'deploy' (ssh deploy@${VPS_IP}),
    lanjut ke 02-install-app.sh
==================================================================
EOF
