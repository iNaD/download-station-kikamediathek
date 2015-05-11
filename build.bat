:: Delete old data
del kikamediathek.host

:: get recent version of the provider base class
copy /Y ..\provider-boilerplate\src\provider.php provider.php

:: create the .tar.gz
7z a -ttar -so kikamediathek INFO kikamediathek.php provider.php | 7z a -si -tgzip kikamediathek.host

del provider.php