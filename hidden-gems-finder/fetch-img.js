const titles = ['Curug Cipamingkis', 'Danau Lau Kawar', 'Pantai Wediombo', 'Bukit Tangkiling', 'Telaga Ngebel', 'Goa Hawang', 'Air terjun Tegenungan', 'Pulau Bair', 'Bakso Pak Kumis Salatiga', 'Nasi Liwet Solo', 'Warung Makan Hj. Mus Klaten'];
Promise.all(titles.map(title =>
  fetch(`https://id.wikipedia.org/w/api.php?action=query&prop=pageimages&titles=${encodeURIComponent(title)}&format=json&pithumbsize=800`)
  .then(r => r.json())
  .then(data => {
    const pages = data.query.pages;
    const pageId = Object.keys(pages)[0];
    return { title, img: pages[pageId].thumbnail ? pages[pageId].thumbnail.source : null };
  })
)).then(console.log);
