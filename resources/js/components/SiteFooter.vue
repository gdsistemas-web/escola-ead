<template>
    <footer class="site-footer site-footer--itapevi" id="contato">
        <div class="footer-overlay"></div>

        <div class="container footer-inner">
            <div class="row g-4 align-items-start">
                <div class="col-lg-4">
                    <div class="footer-brand">
                        <img class="footer-logo" :src="'/assets/img/logo_footer.png'" alt="Camara Municipal de Itapevi">
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="footer-card">
                        <div class="footer-ribbon">LOCALIZACAO</div>
                        <div class="footer-card-body">
                            <strong>Rua Arnaldo Sergio Cordeiro das Neves</strong>
                            <span>Itapevi - SP</span>
                            <small>Das 8h as 17h de Segunda a Sexta-feira</small>
                        </div>
                    </div>

                    <h2 class="footer-subtitle mt-4">Redes Sociais</h2>
                    <div class="footer-social">
                        <a class="footer-social-btn" href="https://www.youtube.com/user/tvcamaraitapevi" target="_blank" rel="noopener" aria-label="YouTube"><i class="ri-youtube-fill"></i></a>
                        <a class="footer-social-btn" href="https://www.flickr.com/photos/158510045@N03/albums" target="_blank" rel="noopener" aria-label="Flickr"><i class="ri-flickr-fill"></i></a>
                        <a class="footer-social-btn" href="https://www.facebook.com/camaraitapevi" target="_blank" rel="noopener" aria-label="Facebook"><i class="ri-facebook-fill"></i></a>
                        <a class="footer-social-btn" href="https://br.linkedin.com/company/camaraitapevi" target="_blank" rel="noopener" aria-label="LinkedIn"><i class="ri-linkedin-fill"></i></a>
                        <a class="footer-social-btn" href="https://www.instagram.com/camaraitapevi" target="_blank" rel="noopener" aria-label="Instagram"><i class="ri-instagram-fill"></i></a>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="footer-card">
                        <div class="footer-ribbon">FALE CONOSCO</div>
                        <div class="footer-card-body">
                            <strong>(11) 4143-7600</strong>
                            <span>escoladoparlamento@itapevi.sp.leg.br</span>
                        </div>
                    </div>

                    <h2 class="footer-subtitle mt-4">Acesso rapido</h2>
                    <div class="footer-apps">
                        <a class="footer-app-btn" href="/professor">
                            <i class="ri-user-star-line"></i>
                            <span><small>Acessar</small><b>Area do professor</b></span>
                        </a>
                        <a class="footer-app-btn" href="/aluno">
                            <i class="ri-login-circle-line"></i>
                            <span><small>Acessar</small><b>Area do aluno</b></span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="container footer-bottom-inner">
                <span>&copy; 2026 Escola do Parlamento de Itapevi.</span>
                <span>Plataforma EAD institucional.</span>
            </div>
        </div>
    </footer>
</template>
