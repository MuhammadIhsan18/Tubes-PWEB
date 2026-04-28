import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { Compass, Map, Star, ArrowRight, Search } from 'lucide-react';
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

  const trendingGems = [
    "Nusa Penida", "Mount Bromo", "Borobudur Temple", "Raja Ampat", 
    "Komodo Island", "Tana Toraja", "Derawan Islands"
  ];

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
      <div className="hero-jib-wrapper" style={{ maxWidth: '1200px', margin: '20px auto', width: '90%', borderRadius: '30px', overflow: 'hidden', position: 'relative', height: '85vh' }}>
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

        {/* Marquee */}
        <div className="marquee-container">
          <div className="marquee-content">
            {[...trendingGems, ...trendingGems].map((gem, i) => (
              <div key={i} className="marquee-item">
                <Star size={14} fill="var(--accent-emerald)" color="var(--accent-emerald)" />
                {gem}
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* Spotlight Section - The Non-Template Addition */}
      <section style={styles.spotlight}>
        <div style={styles.spotlightContent} className="animate-fade-in-up">
          <div style={styles.spotlightText}>
            <span style={{ color: 'var(--accent-emerald)', fontWeight: '800', letterSpacing: '4px', textTransform: 'uppercase', fontSize: '0.8rem' }}>Spotlight of the month</span>
            <h2 style={styles.spotlightTitle}>Raja Ampat: <br/>The Last Paradise</h2>
            <p style={styles.spotlightDesc}>
              A majestic archipelago of over 1,500 small islands, cays, and shoals surrounding the four main islands. It's the crown jewel of Indonesia's hidden gems.
            </p>
            <button className="btn-primary" style={{ marginTop: '20px', padding: '12px 30px' }} onClick={() => navigate('/login')}>
              Explore This Gem
            </button>
          </div>
          <div style={styles.spotlightImageContainer}>
            <div style={styles.spotlightImage} className="hover-glow"></div>
            <div style={styles.spotlightFloatingCard} className="animate-float">
              <div style={{ fontWeight: '800', fontSize: '1.2rem' }}>4.9/5</div>
              <div style={{ fontSize: '0.7rem', color: 'var(--text-muted)' }}>Global Expedition Rating</div>
            </div>
          </div>
        </div>
      </section>

      {/* Floating Elements Section - Landing Effects */}
      <section className="floating-elements-container">
        <div className="main-visual"></div>
        <div className="floating-layer layer-1 animate-fade-in-up delay-100">
          <Map size={24} color="var(--accent-emerald)" />
          <span style={{ fontWeight: '600' }}>GPS Tracking</span>
        </div>
        <div className="floating-layer layer-2 animate-fade-in-up delay-200">
          <Star size={24} color="var(--accent-emerald)" />
          <span style={{ fontWeight: '600' }}>4.9 Rating</span>
        </div>
        <div className="floating-layer layer-3 animate-fade-in-up delay-300">
          <Compass size={24} color="var(--accent-emerald)" />
          <span style={{ fontWeight: '600' }}>Offline Maps</span>
        </div>
        <div className="floating-layer layer-4 animate-fade-in-up delay-400">
          <Search size={24} color="var(--accent-emerald)" />
          <span style={{ fontWeight: '600' }}>Local Secrets</span>
        </div>
        <div style={{ textAlign: 'center', zIndex: 10 }}>
          <h2 style={{ fontSize: '4rem', fontWeight: '800', marginBottom: '10px' }}>Beyond the Map</h2>
          <p style={{ color: 'var(--text-muted)', fontSize: '1.2rem' }}>Experience travel as it was meant to be.</p>
        </div>
      </section>

      {/* Bento Feature Grid */}
      <section className="feature-grid">
        <div className="feature-card feature-card-large animate-fade-in-up delay-100">
          <div className="feature-icon"><Map size={60} /></div>
          <div className="feature-content">
            <h3>Interactive Discovery</h3>
            <p>Our smart maps guide you to locations that aren't listed on standard travel apps, verified by locals.</p>
          </div>
        </div>
        <div className="feature-card feature-card-medium animate-fade-in-up delay-200">
          <div className="feature-icon"><Star size={60} /></div>
          <div className="feature-content">
            <h3>Community Verified</h3>
            <p>Every gem is hand-picked and verified by our global explorer community.</p>
          </div>
        </div>
        <div className="feature-card feature-card-small animate-fade-in-up delay-300">
          <div className="feature-icon"><Compass size={40} /></div>
          <div className="feature-content">
            <h3>Precision Tools</h3>
            <p>Built-in compass and coordinate system.</p>
          </div>
        </div>
        <div className="feature-card feature-card-wide animate-fade-in-up delay-400">
          <div className="feature-icon"><Search size={60} /></div>
          <div className="feature-content">
            <h3>Advanced Search</h3>
            <p>Filter by terrain, difficulty, and distance to find your perfect adventure.</p>
          </div>
        </div>
      </section>

      {/* Footer */}
      <footer className="footer">
        <div style={{ maxWidth: '1200px', margin: '0 auto', width: '90%', display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: '30px' }}>
          <div>
            <div style={{ ...styles.logo, marginBottom: '15px' }}>
              <Compass color="var(--accent-emerald)" size={24} />
              <span style={{ fontWeight: '800', fontSize: '1.1rem', marginLeft: '10px' }}>HiddenGems.</span>
            </div>
            <p style={{ color: 'var(--text-muted)', fontSize: '0.8rem', maxWidth: '300px' }}>
              The world is full of magic things, patiently waiting for our senses to grow sharper.
            </p>
          </div>
          <div style={{ display: 'flex', gap: '40px' }}>
            <div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
              <span style={{ fontWeight: '700', fontSize: '0.9rem' }}>Platform</span>
              <a href="#" style={{ color: 'var(--text-muted)', fontSize: '0.8rem' }}>Discovery</a>
              <a href="#" style={{ color: 'var(--text-muted)', fontSize: '0.8rem' }}>Community</a>
              <a href="#" style={{ color: 'var(--text-muted)', fontSize: '0.8rem' }}>Expeditions</a>
            </div>
            <div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
              <span style={{ fontWeight: '700', fontSize: '0.9rem' }}>Company</span>
              <a href="#" style={{ color: 'var(--text-muted)', fontSize: '0.8rem' }}>About Us</a>
              <a href="#" style={{ color: 'var(--text-muted)', fontSize: '0.8rem' }}>Careers</a>
              <a href="#" style={{ color: 'var(--text-muted)', fontSize: '0.8rem' }}>Contact</a>
            </div>
          </div>
        </div>
        <div style={{ textAlign: 'center', marginTop: '60px', color: 'rgba(255,255,255,0.2)', fontSize: '0.7rem' }}>
          &copy; 2026 HiddenGems Finder. All rights reserved. Built for Explorers.
        </div>
      </footer>
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
  },
  spotlight: {
    maxWidth: '1200px',
    margin: '80px auto',
    width: '90%',
    paddingBottom: '100px',
  },
  spotlightContent: {
    display: 'flex',
    alignItems: 'center',
    gap: '60px',
    flexWrap: 'wrap',
  },
  spotlightText: {
    flex: '1',
    minWidth: '300px',
  },
  spotlightTitle: {
    fontSize: '3.5rem',
    fontWeight: '800',
    lineHeight: '1.1',
    margin: '20px 0',
  },
  spotlightDesc: {
    fontSize: '1.1rem',
    color: 'var(--text-muted)',
    lineHeight: '1.8',
    maxWidth: '500px',
  },
  spotlightImageContainer: {
    flex: '1',
    minWidth: '300px',
    position: 'relative',
    height: '500px',
  },
  spotlightImage: {
    width: '100%',
    height: '100%',
    borderRadius: '40px',
    backgroundImage: 'url("https://images.unsplash.com/photo-1544551763-46a013bb70d5?auto=format&fit=crop&q=80&w=1000")',
    backgroundSize: 'cover',
    backgroundPosition: 'center',
    boxShadow: '0 40px 80px rgba(0,0,0,0.5)',
  },
  spotlightFloatingCard: {
    position: 'absolute',
    bottom: '40px',
    left: '-30px',
    background: 'rgba(31, 17, 11, 0.9)',
    backdropFilter: 'blur(20px)',
    border: '1px solid rgba(245, 158, 11, 0.2)',
    padding: '20px 30px',
    borderRadius: '20px',
    boxShadow: '0 20px 40px rgba(0,0,0,0.3)',
  }
};

export default Home;
