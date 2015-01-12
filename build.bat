:: Delete old data
del kikamediathek.host
:: create the .tar.gz
7z a -ttar -so kikamediathek INFO kikamediathek.php | 7z a -si -tgzip kikamediathek.host
