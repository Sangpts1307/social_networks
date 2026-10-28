export type CompanySection = 'letter' | 'values' | 'principles'

export const companyPages = {
  letter: {
    eyebrow: 'Thông điệp từ Ban Giám đốc',
    title: 'Cùng xây dựng một nơi làm việc tốt hơn',
    intro: 'Mỗi bước tiến của công ty bắt đầu từ sự chủ động, tinh thần hợp tác và niềm tin giữa các thành viên.',
    image: 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=1200&auto=format&fit=crop&q=85',
    sections: [
      { title: 'Lời gửi tới đội ngũ', body: 'Cảm ơn từng thành viên đã dành thời gian, kiến thức và sự tận tâm cho công việc chung. Dù đảm nhận vai trò nào, đóng góp của bạn đều tạo nên trải nghiệm tốt hơn cho đồng nghiệp và khách hàng. Chúng ta sẽ tiếp tục xây dựng một môi trường nơi mọi người có thể trao đổi thẳng thắn, được lắng nghe và được hỗ trợ khi cần.' },
      { title: 'Điều chúng ta hướng tới', body: 'Chúng ta muốn tạo ra sản phẩm hữu ích và một cách làm việc bền vững. Điều đó đòi hỏi mục tiêu rõ ràng, trách nhiệm trong từng quyết định và sự phối hợp giữa các nhóm. Thành công chung không đến từ một cá nhân, mà từ việc mỗi người hiểu phần việc của mình và thấy được tác động của nó.' },
      { title: 'Con người là nền tảng', body: 'Công ty phát triển khi mỗi thành viên có cơ hội học hỏi và trưởng thành. Hãy chia sẻ điều bạn biết, đặt câu hỏi khi chưa rõ và tạo không gian cho người khác nêu ý kiến. Một lời góp ý cụ thể, đúng lúc có thể giúp cả nhóm tránh được nhiều vòng làm lại.' },
      { title: 'Làm việc cùng nhau', body: 'Chúng ta khuyến khích trao đổi sớm về tiến độ, khó khăn và những lựa chọn cần quyết định. Khi có bất đồng, hãy tập trung vào dữ kiện và mục tiêu chung. Sự tôn trọng trong giao tiếp giúp các nhóm đi nhanh hơn mà vẫn giữ được chất lượng công việc.' },
      { title: 'Học từ thực tế', body: 'Không phải kế hoạch nào cũng diễn ra đúng như dự kiến. Mỗi lần thử nghiệm, phản hồi của người dùng hay sự cố trong vận hành đều là cơ hội để nhìn lại cách làm. Chúng ta ghi nhận điều hiệu quả, nói rõ điều chưa hiệu quả và điều chỉnh bằng hành động cụ thể.' },
      { title: 'Cùng tiến về phía trước', body: 'Hãy tiếp tục đề xuất sáng kiến, hỗ trợ đồng đội và ghi lại tri thức để người đến sau có thể bắt đầu thuận lợi hơn. Những cải tiến nhỏ, được duy trì đều đặn, sẽ tạo ra thay đổi lớn cho cả tổ chức.' }
    ]
  },
  values: {
    eyebrow: 'Văn hóa công ty',
    title: 'Giá trị cốt lõi',
    intro: 'Những giá trị chung giúp chúng ta đưa ra quyết định và làm việc với nhau mỗi ngày.',
    image: 'https://images.unsplash.com/photo-1521737711867-e3b97375f902?w=1200&auto=format&fit=crop&q=85',
    sections: [
      { title: 'Chính trực', body: 'Chúng ta trao đổi trung thực về kết quả, rủi ro và giới hạn của công việc. Khi đã nhận trách nhiệm, mỗi người theo sát cam kết và báo sớm nếu cần hỗ trợ. Sự tin cậy được xây từ những việc nhỏ được thực hiện nhất quán.' },
      { title: 'Hợp tác', body: 'Một vấn đề có thể có nhiều góc nhìn. Chúng ta lắng nghe người làm ở các vị trí khác nhau, chia sẻ thông tin cần thiết và cùng tìm phương án phù hợp nhất. Thành quả của nhóm quan trọng hơn việc chứng minh ý kiến cá nhân đúng.' },
      { title: 'Học hỏi', body: 'Sản phẩm và cách làm việc luôn thay đổi. Chúng ta chủ động học từ đồng nghiệp, khách hàng và dữ liệu thực tế; sẵn sàng thử cách mới khi có cơ sở. Kiến thức trở nên có giá trị hơn khi được ghi lại và chia sẻ.' },
      { title: 'Lấy người dùng làm trọng tâm', body: 'Trước mỗi thay đổi, hãy tìm hiểu người sử dụng đang gặp khó khăn gì và kết quả nào thật sự giúp họ. Phản hồi không chỉ là điểm cần sửa mà còn là nguồn thông tin để ưu tiên đúng việc.' },
      { title: 'Chủ động', body: 'Mỗi thành viên có thể nhận diện một điểm cần cải thiện và đề xuất bước tiếp theo. Chủ động không có nghĩa là làm mọi thứ một mình; đó là biết kết nối đúng người, làm rõ vấn đề và đưa công việc tiến lên.' },
      { title: 'Bền vững', body: 'Chúng ta cân bằng tốc độ với chất lượng. Một giải pháp tốt cần dễ hiểu, có thể duy trì và có người chịu trách nhiệm. Cách làm bền vững giúp nhóm giữ nhịp lâu dài, giảm việc lặp lại và dành thời gian cho những cơ hội mới.' }
    ]
  },
  principles: {
    eyebrow: 'Cách chúng ta làm việc',
    title: 'Nguyên tắc làm việc',
    intro: 'Một vài nguyên tắc đơn giản để công việc minh bạch, hiệu quả và dễ phối hợp.',
    image: 'https://images.unsplash.com/photo-1497366811353-6870744d04b2?w=1200&auto=format&fit=crop&q=85',
    sections: [
      { title: 'Rõ mục tiêu', body: 'Trước khi bắt đầu, nhóm thống nhất kết quả cần đạt, người phụ trách, thời hạn và tiêu chí hoàn thành. Khi mục tiêu thay đổi, hãy cập nhật lại phạm vi để mọi người cùng hiểu. Việc làm rõ từ đầu giúp giảm trao đổi vòng vo và tránh bỏ sót trách nhiệm.' },
      { title: 'Giao tiếp kịp thời', body: 'Cập nhật tiến độ theo nhịp phù hợp và nêu vướng mắc ngay khi phát hiện. Một thông tin ngắn gọn gồm bối cảnh, tác động và điều cần hỗ trợ sẽ giúp đồng nghiệp phản hồi nhanh hơn. Đừng để đến sát hạn mới báo một rủi ro đã thấy từ trước.' },
      { title: 'Tôn trọng thời gian', body: 'Mỗi cuộc họp nên có mục tiêu, người tham gia cần thiết và kết luận rõ ràng. Hãy đọc tài liệu trước nếu có, tập trung vào quyết định cần đưa ra và ghi lại việc tiếp theo. Với nội dung chỉ cần cập nhật, ưu tiên trao đổi bằng văn bản.' },
      { title: 'Ghi lại quyết định', body: 'Những quyết định quan trọng cần được lưu ở nơi cả nhóm tìm lại được. Ghi ngắn gọn lựa chọn đã thống nhất, lý do, người chịu trách nhiệm và thời điểm xem lại. Nhờ vậy người mới tham gia dự án không phải tìm hiểu lại từ đầu.' },
      { title: 'Phản hồi cụ thể', body: 'Khi góp ý, hãy nói rõ điểm quan sát được, tác động của nó và đề xuất cải thiện. Phản hồi tập trung vào công việc thay vì con người. Người nhận phản hồi có thể hỏi thêm bối cảnh, thảo luận giải pháp và xác nhận bước tiếp theo.' },
      { title: 'Cải tiến liên tục', body: 'Sau mỗi giai đoạn, dành thời gian nhìn lại điều đã làm tốt và điều đang gây chậm trễ. Chọn một vài thay đổi nhỏ có thể thực hiện ngay, theo dõi kết quả rồi điều chỉnh. Cải tiến có ý nghĩa khi trở thành thói quen của cả nhóm.' }
    ]
  }
} as const

export const insideChannels = [
  { name: 'Tech & Architecture', image: 'https://images.unsplash.com/photo-1518770660439-4636190af475?w=80&auto=format&fit=crop&q=80', cover: 'https://images.unsplash.com/photo-1518770660439-4636190af475?w=1200&auto=format&fit=crop&q=85', description: 'Nơi trao đổi về kiến trúc hệ thống, phát triển sản phẩm và kinh nghiệm kỹ thuật.' },
  { name: 'AI & Knowledge Graph', image: 'https://images.unsplash.com/photo-1635070041078-e363dbe005cb?w=80&auto=format&fit=crop&q=80', cover: 'https://images.unsplash.com/photo-1677442136019-21780ecad995?w=1200&auto=format&fit=crop&q=85', description: 'Cùng chia sẻ thử nghiệm AI, dữ liệu đồ thị và ứng dụng tri thức trong công việc.' },
  { name: 'HR & Văn hóa Doanh nghiệp', image: 'https://images.unsplash.com/photo-1521737711867-e3b97375f902?w=80&auto=format&fit=crop&q=80', cover: 'https://images.unsplash.com/photo-1521737711867-e3b97375f902?w=1200&auto=format&fit=crop&q=85', description: 'Kết nối thành viên, cập nhật hoạt động nhân sự và lan tỏa văn hóa công ty.' }
] as const
