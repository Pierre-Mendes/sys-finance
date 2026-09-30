# 🚀 VPS & GitHub Actions Setup Guide

This guide will help you configure the secure connection between GitHub and your VPS for automated deployments.

---

## 1. Local SSH Configuration

To allow GitHub to connect to your VPS, you need an SSH key pair.

### Generate SSH Key (On your local machine or VPS)
Run this command:
```bash
ssh-keygen -t rsa -b 4096 -C "github-actions-deploy" -f ~/.ssh/id_rsa_github
```

### Authorize the Key on the VPS
Add the public key to the `authorized_keys` file on the VPS:
```bash
cat ~/.ssh/id_rsa_github.pub >> ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys
chmod 700 ~/.ssh
```

---

## 2. GitHub Repository Secrets

Go to **Settings > Secrets and variables > Actions** in your GitHub repository and add these **New repository secrets**:

| Secret Name | Value |
| :--- | :--- |
| `VPS_HOST` | Your VPS IP Address |
| `VPS_USER` | Your SSH Username (e.g., `ubuntu`, `root`) |
| `SSH_PRIVATE_KEY` | Paste the **entire** content of `~/.ssh/id_rsa_github` (the PRIVATE key) |

---

## 3. Environment Variables (VPS)

On your VPS, in the project folder (`~/gerenciador-financeiro-pessoal`), ensure you have the `.env` file configured.

Required variables for Production:
```env
DB_ROOT_PASSWORD=sua_senha_segura
DB_NAME=money_manager
DB_USER=root
DB_PASS=sua_senha_segura
VITE_API_BASE_URL=http://SEU_IP_OU_DOMINIO
```

---

## 4. How to Deploy

### Automatic Sandbox (Port 8082)
- Every time you merge a PR or push to the `main` branch, the system will automatically update the **Sandbox** environment at `http://<IP_DO_SEU_VPS>:8082`.

### Manual Production (Port 80)
1. Go to **Actions** tab on GitHub.
2. Select **Deploy to Production** workflow.
3. Click **Run workflow** -> **Branch: main**.
4. The production site will update at `http://<IP_DO_SEU_VPS>`.

---

## 🔍 Testing Locally

To run all tests locally before pushing:

**Backend:**
```bash
./vendor/bin/phpunit
```

**Frontend:**
```bash
cd frontend && npm run test:unit
```
