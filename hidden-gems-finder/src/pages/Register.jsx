import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Compass, Mail, Lock, User, UserPlus, Eye, EyeOff } from 'lucide-react';

const Register = () => {
  const navigate = useNavigate();
  const [formData, setFormData] = useState({ name: '', email: '', password: '', confirm: '' });
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirm, setShowConfirm] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState(false);
  const [loading, setLoading] = useState(false);

  const handleChange = (e) => {
    setFormData({ ...formData, [e.target.name]: e.target.value });
    setError('');
  };

  const handleRegister = (e) => {
    e.preventDefault();
    setError('');

    if (!formData.name || !formData.email || !formData.password || !formData.confirm) {
      setError('Semua field harus diisi.');
      return;
    }
    if (formData.password.length < 6) {
      setError('Password minimal 6 karakter.');
      return;
    }
    if (formData.password !== formData.confirm) {
      setError('Password dan konfirmasi password tidak cocok.');
      return;
    }

    const users = JSON.parse(localStorage.getItem('hgf_users') || '[]');
    const exists = users.find((u) => u.email === formData.email);
    if (exists) {
      setError('Email ini sudah terdaftar. Silakan gunakan email lain.');
      return;
    }

    setLoading(true);
    setTimeout(() => {
      users.push({ name: formData.name, email: formData.email, password: formData.password });
      localStorage.setItem('hgf_users', JSON.stringify(users));
      setLoading(false);
      setSuccess(true);
      setTimeout(() => navigate('/login'), 2000);
    }, 1000);
  };

  return (
    <div style={styles.container}>
      {/* Animated blobs */}
      <div style={styles.blob1} />
      <div style={styles.blob2} />

      <div style={styles.logo} className="hover-glow" onClick={() => navigate('/')}>
        <Compass color="var(--accent-emerald)" size={32} className="animate-float" />
        <span style={{ fontWeight: '800', fontSize: '1.5rem', marginLeft: '10px' }}>HiddenGems.</span>
      </div>

      <div className="glass-panel" style={styles.card}>
        <div style={styles.cardHeader}>
          <div style={styles.iconCircle} className="hover-glow animate-fade-in-up delay-100">
            <UserPlus color="var(--accent-emerald)" size={28} />
          </div>
          <h2 style={{ marginTop: '16px', fontSize: '1.8rem' }} className="text-gradient animate-fade-in-up delay-200">Buat Akun Baru</h2>
          <p style={{ color: 'var(--text-muted)', marginTop: '6px', fontSize: '0.95rem' }} className="animate-fade-in-up delay-300">
            Mulai petualanganmu hari ini
          </p>
        </div>

        {success ? (
          <div style={styles.successBox}>
            <span style={{ fontSize: '2rem' }}>🎉</span>
            <h3 style={{ marginTop: '12px', color: 'var(--accent-emerald)' }}>Pendaftaran Berhasil!</h3>
            <p style={{ color: 'var(--text-muted)', marginTop: '6px', fontSize: '0.9rem' }}>
              Mengalihkan ke halaman login...
            </p>
          </div>
        ) : (
          <form onSubmit={handleRegister} style={styles.form} className="animate-fade-in-up delay-400">
            {/* Full Name */}
            <div style={styles.inputGroup}>
              <label style={styles.label}>Nama Lengkap</label>
              <div style={styles.inputWrapper} className="sfocus">
                <User size={18} color="var(--text-muted)" style={styles.inputIcon} />
                <input
                  type="text"
                  name="name"
                  placeholder="John Explorer"
                  value={formData.name}
                  onChange={handleChange}
                  style={{ paddingLeft: '44px' }}
                />
              </div>
            </div>

            {/* Email */}
            <div style={styles.inputGroup}>
              <label style={styles.label}>Email</label>
              <div style={styles.inputWrapper} className="sfocus">
                <Mail size={18} color="var(--text-muted)" style={styles.inputIcon} />
                <input
                  type="email"
                  name="email"
                  placeholder="explorer@example.com"
                  value={formData.email}
                  onChange={handleChange}
                  style={{ paddingLeft: '44px' }}
                />
              </div>
            </div>

            {/* Password */}
            <div style={styles.inputGroup}>
              <label style={styles.label}>Password</label>
              <div style={styles.inputWrapper} className="sfocus">
                <Lock size={18} color="var(--text-muted)" style={styles.inputIcon} />
                <input
                  type={showPassword ? 'text' : 'password'}
                  name="password"
                  placeholder="Min. 6 karakter"
                  value={formData.password}
                  onChange={handleChange}
                  style={{ paddingLeft: '44px', paddingRight: '44px' }}
                />
                <button
                  type="button"
                  onClick={() => setShowPassword(!showPassword)}
                  style={styles.eyeBtn}
                >
                  {showPassword ? <EyeOff size={18} color="var(--text-muted)" /> : <Eye size={18} color="var(--text-muted)" />}
                </button>
              </div>
            </div>

            {/* Confirm Password */}
            <div style={styles.inputGroup}>
              <label style={styles.label}>Konfirmasi Password</label>
              <div style={styles.inputWrapper} className="sfocus">
                <Lock size={18} color="var(--text-muted)" style={styles.inputIcon} />
                <input
                  type={showConfirm ? 'text' : 'password'}
                  name="confirm"
                  placeholder="Ulangi password"
                  value={formData.confirm}
                  onChange={handleChange}
                  style={{ paddingLeft: '44px', paddingRight: '44px' }}
                />
                <button
                  type="button"
                  onClick={() => setShowConfirm(!showConfirm)}
                  style={styles.eyeBtn}
                >
                  {showConfirm ? <EyeOff size={18} color="var(--text-muted)" /> : <Eye size={18} color="var(--text-muted)" />}
                </button>
              </div>
            </div>

            {/* Strength Indicator */}
            {formData.password && (
              <div style={styles.strengthBar}>
                <div style={{
                  ...styles.strengthFill,
                  width: formData.password.length >= 10 ? '100%' : formData.password.length >= 6 ? '60%' : '25%',
                  background: formData.password.length >= 10 ? 'var(--accent-emerald)' : formData.password.length >= 6 ? '#f59e0b' : '#ef4444',
                }} />
                <span style={{ fontSize: '0.75rem', color: 'var(--text-muted)', marginTop: '4px' }}>
                  {formData.password.length >= 10 ? 'Kuat' : formData.password.length >= 6 ? 'Sedang' : 'Lemah'}
                </span>
              </div>
            )}

            {/* Error */}
            {error && (
              <div style={styles.errorBox}>
                ⚠️ {error}
              </div>
            )}

            <button type="submit" className="btn-primary" style={styles.submitBtn} disabled={loading}>
              {loading ? (
                <span style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                  <span style={styles.spinner} /> Mendaftarkan...
                </span>
              ) : (
                <span style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                  <UserPlus size={18} /> Daftar Sekarang
                </span>
              )}
            </button>
          </form>
        )}

        <p style={styles.loginLink}>
          Sudah punya akun?{' '}
          <span onClick={() => navigate('/login')} style={styles.link}>
            Masuk di sini
          </span>
        </p>
      </div>

      <style>{`
        @keyframes blob1 {
          0%, 100% { transform: translate(0, 0) scale(1); }
          50% { transform: translate(30px, -20px) scale(1.1); }
        }
        @keyframes blob2 {
          0%, 100% { transform: translate(0, 0) scale(1); }
          50% { transform: translate(-25px, 20px) scale(1.08); }
        }
        @keyframes spin {
          to { transform: rotate(360deg); }
        }
        @keyframes fadeSlideUp {
          from { opacity: 0; transform: translateY(30px); }
          to { opacity: 1; transform: translateY(0); }
        }
        .glass-panel {
          animation: fadeSlideUp 0.6s ease forwards;
        }
      `}</style>
    </div>
  );
};

const styles = {
  container: {
    display: 'flex',
    flexDirection: 'column',
    alignItems: 'center',
    justifyContent: 'center',
    minHeight: '100vh',
    padding: '40px 20px',
    position: 'relative',
    overflow: 'hidden',
  },
  blob1: {
    position: 'fixed',
    width: '500px',
    height: '500px',
    borderRadius: '50%',
    background: 'radial-gradient(circle, rgba(245,158,11,0.12) 0%, transparent 70%)',
    top: '-100px',
    left: '-100px',
    animation: 'blob1 8s ease-in-out infinite',
    pointerEvents: 'none',
  },
  blob2: {
    position: 'fixed',
    width: '450px',
    height: '450px',
    borderRadius: '50%',
    background: 'radial-gradient(circle, rgba(6,182,212,0.1) 0%, transparent 70%)',
    bottom: '-80px',
    right: '-80px',
    animation: 'blob2 10s ease-in-out infinite',
    pointerEvents: 'none',
  },
  logo: {
    display: 'flex',
    alignItems: 'center',
    color: '#fff',
    marginBottom: '32px',
    cursor: 'pointer',
  },
  card: {
    width: '100%',
    maxWidth: '460px',
    padding: '40px',
    zIndex: 1,
  },
  cardHeader: {
    textAlign: 'center',
    marginBottom: '32px',
  },
  iconCircle: {
    width: '64px',
    height: '64px',
    borderRadius: '50%',
    background: 'rgba(245,158,11,0.1)',
    border: '1px solid rgba(245,158,11,0.3)',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    margin: '0 auto',
  },
  form: {
    display: 'flex',
    flexDirection: 'column',
    gap: '18px',
  },
  inputGroup: {
    display: 'flex',
    flexDirection: 'column',
    gap: '6px',
  },
  label: {
    fontSize: '0.85rem',
    fontWeight: '600',
    color: 'var(--text-main)',
  },
  inputWrapper: {
    position: 'relative',
    display: 'flex',
    alignItems: 'center',
  },
  inputIcon: {
    position: 'absolute',
    left: '14px',
  },
  eyeBtn: {
    position: 'absolute',
    right: '12px',
    background: 'transparent',
    border: 'none',
    cursor: 'pointer',
    display: 'flex',
    alignItems: 'center',
    padding: '0',
  },
  strengthBar: {
    display: 'flex',
    flexDirection: 'column',
    gap: '4px',
    marginTop: '-8px',
  },
  strengthFill: {
    height: '4px',
    borderRadius: '4px',
    transition: 'width 0.4s ease, background 0.4s ease',
  },
  errorBox: {
    background: 'rgba(239,68,68,0.1)',
    border: '1px solid rgba(239,68,68,0.3)',
    color: '#fca5a5',
    borderRadius: '8px',
    padding: '12px 16px',
    fontSize: '0.9rem',
  },
  successBox: {
    textAlign: 'center',
    padding: '20px 0',
  },
  submitBtn: {
    display: 'flex',
    justifyContent: 'center',
    alignItems: 'center',
    padding: '14px',
    marginTop: '4px',
    width: '100%',
    fontSize: '1rem',
  },
  spinner: {
    width: '16px',
    height: '16px',
    border: '2px solid rgba(255,255,255,0.3)',
    borderTop: '2px solid white',
    borderRadius: '50%',
    display: 'inline-block',
    animation: 'spin 0.7s linear infinite',
  },
  loginLink: {
    textAlign: 'center',
    marginTop: '24px',
    color: 'var(--text-muted)',
    fontSize: '0.9rem',
  },
  link: {
    color: 'var(--accent-emerald)',
    fontWeight: '600',
    cursor: 'pointer',
  },
};

export default Register;
