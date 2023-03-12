USE `mail`;

DROP TABLE IF EXISTS `myapp_user`;
CREATE TABLE `myapp_user` (
  `id` int(11) NOT NULL,
  `username` varchar(64) NOT NULL,
  `password` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `myapp_user` (`id`, `username`, `password`) VALUES
(21, 't1', 'mamma'),
(22, 't2', 'mamma'),
(23, 'debug', 'mamma');

DROP TABLE IF EXISTS `myapp_notes`;
CREATE TABLE `myapp_notes` (
  `id` int(11) NOT NULL,
  `user` int(11) NOT NULL,
  `cats` varchar(64) NULL,
  `title` varchar(64) NULL,
  `text` varchar(256) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `myapp_notes` (`id`, `user`, `cats`, `title`, `text`) VALUES
(11, 11, 'Cat1,Cat2', 'Title #1', 'This is a short text.\n# comment \nThis <Pitty>tag</Pitty> should survive.\n$%äöü)([]=^\'\"1!\n&nbsp;&auml;#&tag\n--END'),
(12, 11, 'Cat1,Cat2', 'Title #2', 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata '),
(13, 11, 'Cat1,Cat2', 'Title #3', 'Ein Projekt startet und doch es gibt noch keinen Text, allerdings sollte das Layout schon bald präsentiert werden ... was tun?\r\n\r\nDamit das Projekt gleich starten kann benutze einfach etwas Lorem ipsum - Blind-, Füll-, Dummy-, Nachahmungs-, Platzhaltertext'),
(14, 12, '', 'Title #1 - Second account', 'This is a short text.'),
(21, 21, 'Cat1,Cat2', 'Title #1', 'This is a short text.\n# comment \nThis <Pitty>tag</Pitty> should survive.\n$%äöü)([]=^\'\"1!\n&nbsp;&auml;#&tag\n--END'),
(22, 21, 'Cat1,Cat2', 'Title #2', 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata '),
(23, 21, 'Cat1,Cat2', 'Title #3', 'Ein Projekt startet und doch es gibt noch keinen Text, allerdings sollte das Layout schon bald präsentiert werden ... was tun?\r\n\r\nDamit das Projekt gleich starten kann benutze einfach etwas Lorem ipsum - Blind-, Füll-, Dummy-, Nachahmungs-, Platzhaltertext'),
(24, 22, '', 'Title #1 - Second account', 'This is a short text.');

ALTER TABLE `myapp_notes`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `myapp_user`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `myapp_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

ALTER TABLE `myapp_user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;
COMMIT;
