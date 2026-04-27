import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { Compass, Map, Star, ArrowRight } from 'lucide-react';
import '../index.css';

const Home = () => {
  const navigate = useNavigate();
  const [displayText, setDisplayText] = useState('');
  const fullText = "Find the World's Best";
  const [showSub, setShowSub] = useState(false);
  const [subText, setSubText] = useState('');
  const fullSub = "Hidden Gems";

  useEffect(() => {
    let index = 0;
    const timer = setInterval(() => {
      setDisplayText(fullText.substring(0, index));
      index++;
      if (index > fullText.length) {
        clearInterval(timer);
        setTimeout(() => setShowSub(true), 500); // Wait a bit before typing second line
      }
    }, 80);
    return () => clearInterval(timer);
  }, []);

  useEffect(() => {
    if (showSub) {
      let index = 0;
      const timer = setInterval(() => {
        setSubText(fullSub.substring(0, index));
        index++;
        if (index > fullSub.length) clearInterval(timer);
      }, 100);
      return () => clearInterval(timer);
    }
  }, [showSub]);

  return (
    <div className="home-container">
      {/* Navbar */}
      <nav style={styles.navbar} className="glass-panel animate-fade-in-up">
        <div style={styles.logo} className="hover-glow">
          <Compass color="var(--accent-emerald)" size={28} className="animate-float" />
          <span style={{ fontWeight: '800', fontSize: '1.2rem', marginLeft: '10px' }}>HiddenGems.</span>
        </div>
        <div>
          <button className="btn-secondary" style={{ marginRight: '15px' }} onClick={() => navigate('/login')}>
            Sign In
          </button>
          <button className="btn-primary" onClick={() => navigate('/login')}>
            Get Started
          </button>
        </div>
      </nav>

      {/* Hero Section with Jib Effect */}
      <div className="hero-jib-wrapper" style={{ maxWidth: '1200px', margin: '20px auto', width: '90%', borderRadius: '30px', overflow: 'hidden', position: 'relative' }}>
        <div className="hero-jib-bg"></div>
        <header style={styles.hero}>
          <div style={styles.heroContent}>
            <div style={styles.badge} className="animate-fade-in-up delay-100 animate-float">✨ Discover the Unseen</div>
            <h1 style={styles.title}>
              {displayText} {!showSub && <span className="cursor"></span>} <br />
              <span className="text-gradient" style={{ minHeight: '1.2em', display: 'inline-block' }}>
                {subText} {showSub && subText.length < fullSub.length && <span className="cursor"></span>}
              </span>
            </h1>
            <p style={styles.subtitle} className="animate-fade-in-up delay-300">
              Explore secret locations, secluded beaches, and breathtaking landscapes that aren't on the typical tourist map.
            </p>
            <div style={styles.ctaGroup} className="animate-fade-in-up delay-400">
              <button className="btn-primary" style={styles.largeBtn} onClick={() => navigate('/login')}>
                Start Exploring <ArrowRight size={18} style={{ marginLeft: '8px' }} />
              </button>
              <button className="btn-secondary" style={styles.largeBtn} onClick={() => navigate('/register')}>
                Daftar Gratis
              </button>
            </div>
          </div>
        </header>
      </div>

    </div>
  );
};

const styles = {
  navbar: {
    display: 'flex',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: '15px 30px',
    margin: '20px auto',
    width: '90%',
    maxWidth: '1200px',
    borderRadius: '100px',
  },
  logo: {
    display: 'flex',
    alignItems: 'center',
    color: '#fff',
  },
  hero: {
    display: 'flex',
    flexDirection: 'column',
    alignItems: 'center',
    justifyContent: 'center',
    textAlign: 'center',
    minHeight: '70vh',
    padding: '0 20px',
  },
  heroContent: {
    maxWidth: '800px',
    display: 'flex',
    flexDirection: 'column',
    alignItems: 'center',
  },
  badge: {
    background: 'rgba(245, 158, 11, 0.1)',
    color: 'var(--accent-emerald)',
    padding: '8px 16px',
    borderRadius: '50px',
    fontSize: '0.9rem',
    fontWeight: '600',
    marginBottom: '20px',
    border: '1px solid rgba(245, 158, 11, 0.3)',
  },
  title: {
    fontSize: '4.5rem',
    lineHeight: '1.1',
    marginBottom: '20px',
    fontWeight: '800',
  },
  subtitle: {
    fontSize: '1.2rem',
    color: 'var(--text-muted)',
    marginBottom: '40px',
    maxWidth: '600px',
    lineHeight: '1.6',
  },
  ctaGroup: {
    display: 'flex',
    gap: '20px',
  },
  largeBtn: {
    display: 'flex',
    alignItems: 'center',
    padding: '16px 36px',
    fontSize: '1.1rem',
  },
  features: {
    display: 'flex',
    justifyContent: 'center',
    gap: '30px',
    padding: '0 20px 80px 20px',
    flexWrap: 'wrap',
    maxWidth: '1200px',
    margin: '0 auto',
  },
  featureCard: {
    flex: '1',
    minWidth: '280px',
    padding: '30px',
    display: 'flex',
    flexDirection: 'column',
    alignItems: 'flex-start',
  },
  iconBox: {
    background: 'rgba(255,255,255,0.05)',
    padding: '12px',
    borderRadius: '12px',
    marginBottom: '20px',
  }
};

export default Home;
