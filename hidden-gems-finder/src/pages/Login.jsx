import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Compass, Mail, Lock, LogIn, Eye, EyeOff } from 'lucide-react';

const Login = () => {
  const navigate = useNavigate();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const handleLogin = (e) => {
    e.preventDefault();
    setError('');

    if (!email || !password) {
      setError('Email dan password harus diisi.');
      return;
    }

    setLoading(true);
    setTimeout(() => {
      const users = JSON.parse(localStorage.getItem('hgf_users') || '[]');
      const user = users.find((u) => u.email === email && u.password === password);

      if (user) {
        localStorage.setItem('hgf_current_user', JSON.stringify(user));
        navigate('/dashboard');
      } else {
        setError('Email atau password salah. Belum punya akun? Daftar dulu!');
        setLoading(false);
      }
    }, 800);
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

      <div className="glass-panel" style={styles.loginBox}>
        <div style={styles.header}>
          <div style={styles.iconCircle} className="hover-glow animate-fade-in-up delay-100">
            <LogIn color="var(--accent-emerald)" size={26} />
          </div>
          <h2 style={{ marginTop: '16px', fontSize: '1.8rem' }} className="text-gradient animate-fade-in-up delay-200">Selamat Datang!</h2>
          <p style={{ color: 'var(--text-muted)', marginTop: '6px' }} className="animate-fade-in-up delay-300">Masuk untuk melanjutkan eksplorasi</p>
        </div>

        <form onSubmit={handleLogin} style={styles.form} className="animate-fade-in-up delay-400">
          <div style={styles.inputGroup}>
            <label style={styles.label}>Email</label>
            <div style={styles.inputWrapper} className="sfocus">
              <Mail size={18} color="var(--text-muted)" style={styles.inputIcon} />
              <input
                type="email"
                placeholder="explorer@example.com"
                value={email}
                onChange={(e) => { setEmail(e.target.value); setError(''); }}
                required
                style={{ paddingLeft: '44px' }}
              />
            </div>
          </div>

          <div style={styles.inputGroup}>
            <label style={styles.label}>Password</label>
            <div style={styles.inputWrapper} className="sfocus">
              <Lock size={18} color="var(--text-muted)" style={styles.inputIcon} />
              <input
                type={showPassword ? 'text' : 'password'}
                placeholder="••••••••"
                value={password}
                onChange={(e) => { setPassword(e.target.value); setError(''); }}
                required
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

          <div style={styles.options}>
            <label style={{ display: 'flex', alignItems: 'center', gap: '8px', fontSize: '0.9rem', color: 'var(--text-muted)', cursor: 'pointer' }}>
              <input type="checkbox" style={{ width: 'auto' }} /> Ingat saya
            </label>
            <a href="#" style={{ fontSize: '0.9rem', color: 'var(--accent-emerald)' }}>Lupa Password?</a>
          </div>

          {error && (
            <div style={styles.errorBox}>
              ⚠️ {error}
            </div>
          )}

          <button type="submit" className="btn-primary" style={styles.submitBtn} disabled={loading}>
            {loading ? (
              <span style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                <span style={styles.spinner} /> Memverifikasi...
              </span>
            ) : (
              <span style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                <LogIn size={18} /> Masuk
              </span>
            )}
          </button>
        </form>

        <div style={styles.divider}>
          <span style={styles.dividerLine} />
          <span style={{ color: 'var(--text-muted)', fontSize: '0.85rem', padding: '0 12px' }}>atau</span>
          <span style={styles.dividerLine} />
        </div>

        <p style={{ textAlign: 'center', color: 'var(--text-muted)', fontSize: '0.9rem' }}>
          Belum punya akun?{' '}
          <span
            onClick={() => navigate('/register')}
            style={{ color: 'var(--accent-emerald)', fontWeight: '600', cursor: 'pointer' }}
          >
            Daftar Sekarang
          </span>
        </p>
      </div>

      <style>{`
        @keyframes blob1 { 0%, 100% { transform: translate(0,0) scale(1); } 50% { transform: translate(30px,-20px) scale(1.1); } }
        @keyframes blob2 { 0%, 100% { transform: translate(0,0) scale(1); } 50% { transform: translate(-25px,20px) scale(1.08); } }
        @keyframes spin { to { transform: rotate(360deg); } }
        @keyframes fadeSlideUp { from { opacity:0; transform:translateY(30px); } to { opacity:1; transform:translateY(0); } }
        .glass-panel { animation: fadeSlideUp 0.6s ease forwards; }
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
  loginBox: {
    width: '100%',
    maxWidth: '440px',
    padding: '40px',
    zIndex: 1,
  },
  header: {
    textAlign: 'center',
    marginBottom: '30px',
  },
  iconCircle: {
    width: '60px',
    height: '60px',
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
    gap: '20px',
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
  options: {
    display: 'flex',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginTop: '-4px',
  },
  errorBox: {
    background: 'rgba(239,68,68,0.1)',
    border: '1px solid rgba(239,68,68,0.3)',
    color: '#fca5a5',
    borderRadius: '8px',
    padding: '12px 16px',
    fontSize: '0.9rem',
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
  divider: {
    display: 'flex',
    alignItems: 'center',
    margin: '24px 0 20px',
  },
  dividerLine: {
    flex: 1,
    height: '1px',
    background: 'var(--glass-border)',
  },
};

export default Login;
